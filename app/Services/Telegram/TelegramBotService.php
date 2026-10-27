<?php

namespace App\Services\Telegram;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Telegram\AccountLinked;
use App\Services\TicketService;
use App\Support\OfficeLocationTranslator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Handles Telegram updates (from long polling or a webhook).
 *
 * Members: /new (guided ticket), /my (follow tickets), reply to a notification,
 * confirm or reject a finished ticket.
 * Admins: take / reply / mark finished from the ticket card.
 * Group chats only receive notifications; no actions are accepted there.
 */
class TelegramBotService
{
    private const STATE_TTL_MINUTES = 15;
    private const MAX_PHOTOS = 3;
    private const MAX_FILE_BYTES = 2 * 1024 * 1024;
    private const RATE_LIMIT_PER_MINUTE = 30;

    public function __construct(
        private TelegramApi $api,
        private TicketService $tickets,
    ) {}

    public function handle(array $update): void
    {
        try {
            if (isset($update['callback_query'])) {
                $this->handleCallback($update['callback_query']);
            } elseif (isset($update['message'])) {
                $this->handleMessage($update['message']);
            }
        } catch (\Throwable $e) {
            Log::error('Telegram update failed', ['update_id' => $update['update_id'] ?? null, 'error' => $e->getMessage()]);
        }
    }

    // ------------------------------------------------------------------ messages

    private function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'] ?? null;
        if (!$chatId || ($message['chat']['type'] ?? '') !== 'private') {
            return; // department groups: notifications only
        }

        $text = trim($message['text'] ?? $message['caption'] ?? '');

        if (preg_match('/^\/start\s+(\S+)/', $text, $m)) {
            $this->linkAccount($chatId, $m[1]);
            return;
        }

        $user = $this->linkedUser($chatId);
        if (!$user) {
            $this->api->sendMessage($chatId, "مرحباً بك!\nرقم المعرف الخاص بك هو: {$chatId}\n\nلربط حسابك افتح صفحة \"حسابي\" في نظام الدعم الفني واضغط \"ربط الحساب\".");
            return;
        }

        if ($this->rateLimited($chatId)) {
            return;
        }

        $command = Str::before(Str::lower($text), '@');
        switch ($command) {
            case '/start':
            case '/help':
            case '/menu':
                $this->clearState($chatId);
                $this->sendMenu($chatId, $user);
                return;
            case '/new':
                $this->startNewTicket($chatId, $user);
                return;
            case '/my':
                $this->clearState($chatId);
                $this->sendMyTickets($chatId, $user);
                return;
            case '/cancel':
                $this->clearState($chatId);
                $this->api->sendMessage($chatId, 'تم الإلغاء.', $this->menuKeyboard());
                return;
        }

        // Replying (swipe-reply) to any message that mentions "#123" adds a reply to that ticket
        $repliedText = $message['reply_to_message']['text'] ?? $message['reply_to_message']['caption'] ?? '';
        if ($repliedText && preg_match('/#(\d+)/u', $repliedText, $m)) {
            $this->addReply($chatId, $user, (int) $m[1], $message);
            return;
        }

        $state = $this->getState($chatId);
        if ($state && $state['flow'] === 'new') {
            $this->continueNewTicket($chatId, $user, $state, $message, $text);
            return;
        }
        if ($state && $state['flow'] === 'reply') {
            $this->clearState($chatId);
            $this->addReply($chatId, $user, (int) $state['ticket_id'], $message);
            return;
        }

        $this->sendMenu($chatId, $user);
    }

    private function linkAccount(int|string $chatId, string $token): void
    {
        $user = User::where('telegram_link_token', $token)->first();
        if (!$user) {
            $this->api->sendMessage($chatId, 'عذراً، رمز الربط غير صالح أو تم استخدامه مسبقاً. أنشئ رابطاً جديداً من صفحة "حسابي".');
            return;
        }

        // a chat can belong to one account only
        User::where('telegram_number', (string) $chatId)->where('id', '!=', $user->id)->update(['telegram_number' => null]);

        $user->telegram_number = (string) $chatId;
        $user->telegram_linked_at = now();
        $user->telegram_link_token = null;
        $user->save();

        try {
            $user->notify(new AccountLinked());
        } catch (\Throwable $e) {
            Log::warning('AccountLinked notification failed', ['user' => $user->id, 'error' => $e->getMessage()]);
        }

        $this->sendMenu($chatId, $user);
    }

    // ------------------------------------------------------------------ callbacks

    private function handleCallback(array $callback): void
    {
        $chatId = $callback['message']['chat']['id'] ?? null;
        $data = $callback['data'] ?? '';
        $user = $chatId ? $this->linkedUser($chatId) : null;

        if (!$user || ($callback['message']['chat']['type'] ?? '') !== 'private') {
            $this->api->answerCallback($callback['id'], 'يجب ربط حسابك أولاً من خلال المحادثة الخاصة مع البوت.');
            return;
        }
        if ($this->rateLimited($chatId)) {
            $this->api->answerCallback($callback['id'], 'طلبات كثيرة، حاول بعد قليل.');
            return;
        }

        $this->api->answerCallback($callback['id']);

        [$action, $arg] = array_pad(explode(':', $data, 2), 2, null);

        match ($action) {
            'menu' => $arg === 'new' ? $this->startNewTicket($chatId, $user) : $this->sendMyTickets($chatId, $user),
            'cat', 'loc', 'skip', 'new' => $this->continueNewTicketCallback($chatId, $user, $action, $arg),
            't' => $this->sendTicket($chatId, $user, (int) $arg),
            'r' => $this->askReply($chatId, $user, (int) $arg),
            'c' => $this->ticketAction($chatId, $user, (int) $arg, 'confirm'),
            'nf' => $this->ticketAction($chatId, $user, (int) $arg, 'notfixed'),
            'tk' => $this->ticketAction($chatId, $user, (int) $arg, 'take'),
            'f' => $this->ticketAction($chatId, $user, (int) $arg, 'finish'),
            default => null,
        };
    }

    // ------------------------------------------------------------------ new ticket flow

    private function startNewTicket(int|string $chatId, User $user): void
    {
        if ($this->tickets->hasTicketAwaitingConfirmation($user)) {
            $this->clearState($chatId);
            $this->api->sendMessage($chatId, 'لديك تذكرة منتهية بانتظار تأكيدك، يرجى تأكيدها أولاً قبل إنشاء تذكرة جديدة.', [
                'inline_keyboard' => [[['text' => 'عرض تذاكري', 'callback_data' => 'menu:my']]],
            ]);
            return;
        }

        $this->putState($chatId, ['flow' => 'new', 'step' => 'category', 'data' => [], 'photos' => []]);

        $rows = Category::orderBy('name')->get()
            ->map(fn ($c) => ['text' => Str::limit($c->name, 30), 'callback_data' => "cat:{$c->id}"])
            ->chunk(2)->map(fn ($row) => $row->values()->all())->values()->all();

        $this->api->sendMessage($chatId, "تذكرة جديدة (1/5)\nاختر نوع المشكلة:\n\n(لإلغاء العملية أرسل /cancel)", ['inline_keyboard' => $rows]);
    }

    private function continueNewTicketCallback(int|string $chatId, User $user, string $action, ?string $arg): void
    {
        $state = $this->getState($chatId);
        if (!$state || $state['flow'] !== 'new') {
            $this->api->sendMessage($chatId, 'انتهت صلاحية هذه الخطوة. ابدأ من جديد.', $this->menuKeyboard());
            return;
        }

        if ($action === 'cat' && $state['step'] === 'category') {
            $category = Category::find((int) $arg);
            if (!$category) {
                return;
            }
            $state['data']['category_id'] = $category->id;
            $state['data']['category_name'] = $category->name;
            $state['step'] = 'location';
            $this->putState($chatId, $state);

            $keyboard = null;
            if ($user->office_location) {
                $keyboard = ['inline_keyboard' => [[['text' => 'مكان عملي: ' . Str::limit(OfficeLocationTranslator::toArabic($user->office_location), 40), 'callback_data' => 'loc:same']]]];
            }
            $this->api->sendMessage($chatId, "(2/5) اكتب مكان المشكلة (القسم / الغرفة):", $keyboard);
            return;
        }

        if ($action === 'loc' && $state['step'] === 'location') {
            $state['data']['office_location'] = OfficeLocationTranslator::toArabic($user->office_location);
            $state['step'] = 'title';
            $this->putState($chatId, $state);
            $this->api->sendMessage($chatId, '(3/5) اكتب عنواناً قصيراً للمشكلة:');
            return;
        }

        if ($action === 'skip' && $state['step'] === 'photo') {
            $state['step'] = 'confirm';
            $this->putState($chatId, $state);
            $this->sendConfirmation($chatId, $state);
            return;
        }

        if ($action === 'new' && $arg === 'cancel') {
            $this->clearState($chatId);
            $this->api->sendMessage($chatId, 'تم إلغاء التذكرة.', $this->menuKeyboard());
            return;
        }

        if ($action === 'new' && $arg === 'send' && $state['step'] === 'confirm') {
            $this->submitNewTicket($chatId, $user, $state);
        }
    }

    private function continueNewTicket(int|string $chatId, User $user, array $state, array $message, string $text): void
    {
        switch ($state['step']) {
            case 'category':
                $this->api->sendMessage($chatId, 'يرجى اختيار نوع المشكلة من الأزرار أعلاه.');
                return;

            case 'location':
                if ($text === '') {
                    $this->api->sendMessage($chatId, 'يرجى كتابة مكان المشكلة.');
                    return;
                }
                $state['data']['office_location'] = Str::limit($text, 250, '');
                $state['step'] = 'title';
                $this->putState($chatId, $state);
                $this->api->sendMessage($chatId, '(3/5) اكتب عنواناً قصيراً للمشكلة:');
                return;

            case 'title':
                if ($text === '') {
                    $this->api->sendMessage($chatId, 'يرجى كتابة عنوان للمشكلة.');
                    return;
                }
                $state['data']['title'] = Str::limit($text, 250, '');
                $state['step'] = 'description';
                $this->putState($chatId, $state);
                $this->api->sendMessage($chatId, '(4/5) اشرح المشكلة بالتفصيل:');
                return;

            case 'description':
                if ($text === '') {
                    $this->api->sendMessage($chatId, 'يرجى كتابة وصف المشكلة.');
                    return;
                }
                $state['data']['description'] = $text;
                $state['step'] = 'photo';
                $this->putState($chatId, $state);
                $this->api->sendMessage($chatId, "(5/5) أرسل صورة للمشكلة (حتى " . self::MAX_PHOTOS . " صور)، أو اضغط تخطي.", [
                    'inline_keyboard' => [[['text' => 'تخطي / متابعة', 'callback_data' => 'skip:photo']]],
                ]);
                return;

            case 'photo':
                $fileId = $this->bestPhotoId($message);
                if (!$fileId) {
                    $this->api->sendMessage($chatId, 'أرسل صورة، أو اضغط "تخطي / متابعة".', [
                        'inline_keyboard' => [[['text' => 'تخطي / متابعة', 'callback_data' => 'skip:photo']]],
                    ]);
                    return;
                }
                $state['photos'][] = $fileId;
                if (count($state['photos']) >= self::MAX_PHOTOS) {
                    $state['step'] = 'confirm';
                    $this->putState($chatId, $state);
                    $this->sendConfirmation($chatId, $state);
                    return;
                }
                $this->putState($chatId, $state);
                $this->api->sendMessage($chatId, 'تمت إضافة الصورة (' . count($state['photos']) . '/' . self::MAX_PHOTOS . '). أرسل صورة أخرى أو اضغط متابعة.', [
                    'inline_keyboard' => [[['text' => 'متابعة', 'callback_data' => 'skip:photo']]],
                ]);
                return;

            case 'confirm':
                $this->sendConfirmation($chatId, $state);
                return;
        }
    }

    private function sendConfirmation(int|string $chatId, array $state): void
    {
        $d = $state['data'];
        $summary = "راجع التذكرة قبل الإرسال:\n\n"
            . "نوع المشكلة: {$d['category_name']}\n"
            . "المكان: {$d['office_location']}\n"
            . "العنوان: {$d['title']}\n"
            . "الوصف: {$d['description']}\n"
            . 'الصور: ' . count($state['photos']);

        $this->api->sendMessage($chatId, $summary, ['inline_keyboard' => [[
            ['text' => 'إرسال التذكرة', 'callback_data' => 'new:send'],
            ['text' => 'إلغاء', 'callback_data' => 'new:cancel'],
        ]]]);
    }

    private function submitNewTicket(int|string $chatId, User $user, array $state): void
    {
        $this->clearState($chatId); // prevents double submission on repeated taps

        $files = [];
        foreach ($state['photos'] as $index => $fileId) {
            $contents = $this->api->downloadFile($fileId, self::MAX_FILE_BYTES);
            if ($contents !== null) {
                $files[] = ['contents' => $contents, 'name' => 'telegram-photo-' . ($index + 1) . '.jpg', 'extension' => 'jpg'];
            }
        }

        try {
            $ticket = $this->tickets->create($user, $state['data'], $files);
        } catch (\DomainException $e) {
            $this->api->sendMessage($chatId, $e->getMessage(), $this->menuKeyboard());
            return;
        }

        $this->api->sendMessage($chatId, "تم إنشاء التذكرة #{$ticket->id} بنجاح. سيصلك إشعار عند الرد عليها.", [
            'inline_keyboard' => [[['text' => "عرض التذكرة #{$ticket->id}", 'callback_data' => "t:{$ticket->id}"]]],
        ]);
    }

    // ------------------------------------------------------------------ follow tickets

    private function sendMyTickets(int|string $chatId, User $user): void
    {
        $isAdmin = $user->role?->name === 'Admin';

        $query = Ticket::where('status', '!=', 'closed')->latest()->limit(10);
        $isAdmin
            ? $query->where(fn ($q) => $q->where('created_by', $user->id)->orWhereHas('assignedUsers', fn ($a) => $a->where('users.id', $user->id)))
            : $query->where('created_by', $user->id);
        $tickets = $query->get();

        if ($tickets->isEmpty()) {
            $this->api->sendMessage($chatId, 'لا توجد تذاكر مفتوحة.', $this->menuKeyboard());
            return;
        }

        $rows = $tickets->map(fn ($t) => [[
            'text' => Str::limit("#{$t->id} {$t->title} ({$t->status_label})", 60),
            'callback_data' => "t:{$t->id}",
        ]])->all();

        $title = $isAdmin ? 'تذاكري والتذاكر المكلف بها (آخر 10):' : 'تذاكري المفتوحة (آخر 10):';
        $this->api->sendMessage($chatId, $title, ['inline_keyboard' => $rows]);
    }

    private function sendTicket(int|string $chatId, User $user, int $ticketId): void
    {
        $ticket = Ticket::with(['category', 'assignedUsers', 'messages.user'])->find($ticketId);
        if (!$ticket || !$user->can('view', $ticket)) {
            $this->api->sendMessage($chatId, 'التذكرة غير موجودة أو لا تملك صلاحية عرضها.');
            return;
        }

        $assignees = $ticket->assignedUsers->pluck('fullname')->join('، ') ?: 'لم تُستلم بعد';
        $lines = [
            "تذكرة #{$ticket->id}: {$ticket->title}",
            "الحالة: {$ticket->status_label}",
            'نوع المشكلة: ' . ($ticket->category->name ?? '—'),
            "المسؤول: {$assignees}",
            'أنشئت: ' . $ticket->created_at->format('Y-m-d H:i'),
        ];

        $recent = $ticket->messages->sortByDesc('created_at')->take(3)->reverse();
        if ($recent->isNotEmpty()) {
            $lines[] = "\nآخر الردود:";
            foreach ($recent as $msg) {
                $lines[] = '- ' . ($msg->user->fullname ?? '؟') . ': ' . Str::limit($msg->content, 120);
            }
        }

        $this->api->sendMessage($chatId, implode("\n", $lines), ['inline_keyboard' => $this->ticketButtons($user, $ticket)]);
    }

    private function ticketButtons(User $user, Ticket $ticket): array
    {
        $rows = [];
        $isCreator = $ticket->created_by === $user->id;
        $isAdmin = $user->role?->name === 'Admin';
        $canUpdate = $isAdmin && $user->can('update', $ticket);

        if ($isCreator && $ticket->status === 'finished') {
            $rows[] = [
                ['text' => 'تأكيد الحل', 'callback_data' => "c:{$ticket->id}"],
                ['text' => 'المشكلة مستمرة', 'callback_data' => "nf:{$ticket->id}"],
            ];
        }

        if ($canUpdate && !in_array($ticket->status, ['finished', 'closed'])) {
            $row = [];
            if (!$ticket->assignedUsers->contains($user->id)) {
                $row[] = ['text' => 'استلام', 'callback_data' => "tk:{$ticket->id}"];
            }
            $row[] = ['text' => 'تم الحل', 'callback_data' => "f:{$ticket->id}"];
            $rows[] = $row;
        }

        if ($ticket->status !== 'closed' && ($isCreator || $isAdmin)) {
            $rows[] = [['text' => 'رد على التذكرة', 'callback_data' => "r:{$ticket->id}"]];
        }

        return $rows;
    }

    private function askReply(int|string $chatId, User $user, int $ticketId): void
    {
        $ticket = Ticket::find($ticketId);
        if (!$ticket || !$user->can('view', $ticket) || $ticket->status === 'closed') {
            $this->api->sendMessage($chatId, 'لا يمكن الرد على هذه التذكرة.');
            return;
        }

        $this->putState($chatId, ['flow' => 'reply', 'ticket_id' => $ticketId]);
        $this->api->sendMessage($chatId, "اكتب ردك على التذكرة #{$ticketId} (يمكنك إرسال صورة أيضاً)، أو /cancel للإلغاء:");
    }

    private function addReply(int|string $chatId, User $user, int $ticketId, array $message): void
    {
        $ticket = Ticket::find($ticketId);
        if (!$ticket || !$user->can('view', $ticket) || $ticket->status === 'closed') {
            $this->api->sendMessage($chatId, "لا يمكن الرد على التذكرة #{$ticketId}.");
            return;
        }

        $text = trim($message['text'] ?? $message['caption'] ?? '');
        $fileId = $this->bestPhotoId($message);

        try {
            if ($fileId && ($contents = $this->api->downloadFile($fileId, self::MAX_FILE_BYTES)) !== null) {
                $this->tickets->storeAttachment($ticket, ['contents' => $contents, 'name' => 'telegram-photo.jpg', 'extension' => 'jpg']);
                $text = $text !== '' ? $text : 'أرفق صورة من تيليجرام';
            }

            if ($text === '') {
                $this->api->sendMessage($chatId, 'الرد فارغ. اكتب نص الرد.');
                return;
            }

            $this->tickets->addMessage($user, $ticket, $text);
        } catch (AuthorizationException $e) {
            $this->api->sendMessage($chatId, 'لا تملك صلاحية الرد على هذه التذكرة.');
            return;
        }

        $this->api->sendMessage($chatId, "تم إرسال ردك على التذكرة #{$ticketId}.", [
            'inline_keyboard' => [[['text' => "عرض التذكرة #{$ticketId}", 'callback_data' => "t:{$ticketId}"]]],
        ]);
    }

    private function ticketAction(int|string $chatId, User $user, int $ticketId, string $action): void
    {
        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            $this->api->sendMessage($chatId, 'التذكرة غير موجودة.');
            return;
        }

        try {
            switch ($action) {
                case 'confirm':
                    if ($ticket->status !== 'finished') {
                        $this->api->sendMessage($chatId, "التذكرة #{$ticketId} ليست بانتظار التأكيد.");
                        return;
                    }
                    $this->tickets->confirmClose($user, $ticket);
                    $this->api->sendMessage($chatId, "شكراً لك! تم إغلاق التذكرة #{$ticketId}.", $this->menuKeyboard());
                    return;
                case 'notfixed':
                    $this->tickets->reportNotFixed($user, $ticket);
                    $this->api->sendMessage($chatId, "تم إبلاغ الدعم الفني وإعادة التذكرة #{$ticketId} إلى قيد العمل. يمكنك إضافة تفاصيل بالرد على التذكرة.", [
                        'inline_keyboard' => [[['text' => 'رد على التذكرة', 'callback_data' => "r:{$ticketId}"]]],
                    ]);
                    return;
                case 'take':
                    if (in_array($ticket->status, ['finished', 'closed'])) {
                        $this->api->sendMessage($chatId, "التذكرة #{$ticketId} منتهية بالفعل.");
                        return;
                    }
                    $this->tickets->take($user, $ticket);
                    $this->api->sendMessage($chatId, "تم استلام التذكرة #{$ticketId}.");
                    $this->sendTicket($chatId, $user, $ticketId);
                    return;
                case 'finish':
                    if (in_array($ticket->status, ['finished', 'closed'])) {
                        $this->api->sendMessage($chatId, "التذكرة #{$ticketId} منتهية بالفعل.");
                        return;
                    }
                    $this->tickets->markFinished($user, $ticket);
                    $this->api->sendMessage($chatId, "تم تحديد التذكرة #{$ticketId} كمنتهية. بانتظار تأكيد صاحب التذكرة.");
                    return;
            }
        } catch (AuthorizationException $e) {
            $this->api->sendMessage($chatId, 'لا تملك صلاحية تنفيذ هذا الإجراء.');
        } catch (\DomainException $e) {
            $this->api->sendMessage($chatId, $e->getMessage());
        }
    }

    // ------------------------------------------------------------------ helpers

    private function sendMenu(int|string $chatId, User $user): void
    {
        $this->api->sendMessage(
            $chatId,
            "مرحباً {$user->fullname}\n\n"
            . "/new - فتح تذكرة جديدة\n"
            . "/my - متابعة تذاكري\n"
            . "/cancel - إلغاء العملية الحالية\n\n"
            . "للرد على تذكرة: اضغط مطولاً على أي إشعار يحتوي رقم التذكرة واختر \"رد\".\n"
            . 'تنبيه: لا ترسل بيانات المرضى أو معلومات طبية عبر تيليجرام.',
            $this->menuKeyboard()
        );
    }

    private function menuKeyboard(): array
    {
        return ['inline_keyboard' => [[
            ['text' => 'تذكرة جديدة', 'callback_data' => 'menu:new'],
            ['text' => 'تذاكري', 'callback_data' => 'menu:my'],
        ]]];
    }

    private function linkedUser(int|string $chatId): ?User
    {
        return User::where('telegram_number', (string) $chatId)->first();
    }

    /**
     * Largest photo size that still fits the 2MB attachment limit.
     */
    private function bestPhotoId(array $message): ?string
    {
        $sizes = collect($message['photo'] ?? [])
            ->filter(fn ($p) => ($p['file_size'] ?? 0) <= self::MAX_FILE_BYTES)
            ->sortByDesc('file_size');

        return $sizes->first()['file_id'] ?? null;
    }

    private function rateLimited(int|string $chatId): bool
    {
        $key = "telegram-bot:{$chatId}";
        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_PER_MINUTE)) {
            return true;
        }
        RateLimiter::hit($key, 60);

        return false;
    }

    private function getState(int|string $chatId): ?array
    {
        return Cache::get("telegram-bot:state:{$chatId}");
    }

    private function putState(int|string $chatId, array $state): void
    {
        Cache::put("telegram-bot:state:{$chatId}", $state, now()->addMinutes(self::STATE_TTL_MINUTES));
    }

    private function clearState(int|string $chatId): void
    {
        Cache::forget("telegram-bot:state:{$chatId}");
    }
}
