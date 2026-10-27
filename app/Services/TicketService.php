<?php

namespace App\Services;

use App\Http\Traits\FilterAdminsByHospital;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\User;
use App\Notifications\RealTimeNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;

/**
 * Ticket actions shared by the web controllers and the Telegram bot,
 * so both channels apply the same rules, logs and notifications.
 */
class TicketService
{
    use FilterAdminsByHospital;

    /**
     * @throws \DomainException when the user still has a finished ticket awaiting confirmation
     */
    public function create(User $user, array $data, array $files = [], ?string $ip = null, ?string $hostName = null, array $ips = []): Ticket
    {
        if ($this->hasTicketAwaitingConfirmation($user)) {
            throw new \DomainException('لديك تذكرة منتهية بانتظار تأكيدك، يرجى إغلاقها أولاً قبل إنشاء تذكرة جديدة.');
        }

        $ticket = Ticket::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'office_location' => $data['office_location'],
            'category_id' => $data['category_id'],
            'created_by' => $user->id,
            'status' => 'open',
            'ip_address' => $ip,
            'host_name' => $hostName,
            'ip_addresses' => !empty($ips) ? $ips : null,
        ]);

        foreach ($files as $file) {
            $this->storeAttachment($ticket, $file);
        }

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'content' => 'تم إنشاء التذكرة بواسطة ' . $user->fullname,
        ]);

        $message = 'تذكرة جديدة في قسمك من: ' . $user->fullname;
        foreach ($this->getUsersAllowedToView($ticket) as $admin) {
            $this->notify($admin, new RealTimeNotification($message, $ticket->id, $admin->id));
        }
        $this->notifyDepartmentChats($ticket, $message);
        $this->notify($user, new RealTimeNotification('تم استلام تذكرتك الجديدة بنجاح رقم #' . $ticket->id, $ticket->id, $user->id));

        return $ticket;
    }

    public function hasTicketAwaitingConfirmation(User $user): bool
    {
        return Ticket::where('created_by', $user->id)->where('status', 'finished')->exists();
    }

    /**
     * @param UploadedFile|array{contents:string,name:string,extension:string} $file
     */
    public function storeAttachment(Ticket $ticket, UploadedFile|array $file)
    {
        if ($file instanceof UploadedFile) {
            $path = $file->store('tickets/attachments', 'public');
            $name = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
        } else {
            $path = 'tickets/attachments/' . Str::random(40) . '.' . $file['extension'];
            Storage::disk('public')->put($path, $file['contents']);
            $name = $file['name'];
            $extension = $file['extension'];
        }

        return $ticket->attachments()->create([
            'file_path' => $path,
            'file_name' => $name,
            'file_type' => $extension,
        ]);
    }

    /**
     * @throws AuthorizationException when the user is neither an admin nor the ticket creator
     */
    public function addMessage(User $user, Ticket $ticket, string $content): Message
    {
        if ($user->role->name != 'Admin' && $ticket->created_by != $user->id) {
            throw new AuthorizationException();
        }

        $newMessage = Message::create([
            'ticket_id' => $ticket->id,
            'content' => $content,
            'created_by' => $user->id,
        ]);

        $shortContent = Str::limit($newMessage->content, 50);
        $notificationBody = "رد جديد على تذكرة #{$ticket->id} من {$user->fullname}: \"{$shortContent}\"";

        foreach ($this->getUsersAllowedToView($ticket) as $admin) {
            if ($admin->id !== $user->id) {
                $this->notify($admin, new RealTimeNotification($notificationBody, $ticket->id, $admin->id));
            }
        }
        $this->notifyDepartmentChats($ticket, $notificationBody);

        if ($ticket->created_by !== $user->id) {
            $this->notify($ticket->creator, new RealTimeNotification($notificationBody, $ticket->id, $ticket->created_by));
        }

        // first admin reply moves the ticket to "in progress" and assigns it
        if ($user->role->name == 'Admin') {
            $adminReplyCount = $ticket->messages()->whereHas('user.role', function ($q) {
                $q->where('name', 'Admin');
            })->count();

            if ($adminReplyCount === 1) {
                $ticket->update(['status' => 'in_progress']);
                $ticket->assignedUsers()->syncWithoutDetaching([$user->id]);

                TicketLog::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'content' => "تم تغيير حالة التذكرة إلى قيد العمل وتعيينها تلقائياً إلى {$user->fullname} ",
                ]);
            }
        }

        return $newMessage;
    }

    /**
     * Creator confirms the fix: finished -> closed.
     */
    public function confirmClose(User $user, Ticket $ticket): void
    {
        if ($user->id !== $ticket->created_by) {
            throw new AuthorizationException();
        }

        $ticket->update(['status' => 'closed']);

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'content' => 'قام المستخدم بتأكيد حل التذكرة وإغلاقها نهائياً.',
        ]);

        $message = "قام المستخدم {$user->fullname} بإغلاق التذكرة رقم #{$ticket->id}";
        foreach ($this->getUsersAllowedToView($ticket) as $admin) {
            $this->notify($admin, new RealTimeNotification($message, $ticket->id, $admin->id));
        }
        $this->notifyDepartmentChats($ticket, $message);
    }

    /**
     * Creator says the problem is not fixed: finished -> in_progress.
     */
    public function reportNotFixed(User $user, Ticket $ticket): void
    {
        if ($user->id !== $ticket->created_by) {
            throw new AuthorizationException();
        }
        if ($ticket->status !== 'finished') {
            throw new \DomainException('لا يمكن تنفيذ هذا الإجراء على تذكرة غير منتهية.');
        }

        $ticket->update(['status' => 'in_progress']);

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'content' => 'أفاد المستخدم بأن المشكلة ما زالت قائمة وتمت إعادة التذكرة إلى قيد العمل.',
        ]);

        $message = "أفاد {$user->fullname} بأن مشكلة التذكرة #{$ticket->id} ما زالت قائمة";
        foreach ($this->getUsersAllowedToView($ticket) as $admin) {
            $this->notify($admin, new RealTimeNotification($message, $ticket->id, $admin->id));
        }
        $this->notifyDepartmentChats($ticket, $message);
    }

    /**
     * Admin takes the ticket: assign self and move to in_progress.
     */
    public function take(User $admin, Ticket $ticket): void
    {
        $this->authorizeUpdate($admin, $ticket);

        $ticket->assignedUsers()->syncWithoutDetaching([$admin->id]);
        $oldStatus = $ticket->status_label;
        if (in_array($ticket->status, ['open', 'pending'])) {
            $ticket->update(['status' => 'in_progress']);
        }

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'content' => "قام {$admin->fullname} باستلام التذكرة",
        ]);

        if ($ticket->status_label !== $oldStatus) {
            $this->notify($ticket->creator, new RealTimeNotification(
                "تم تغيير حالة التذكرة #{$ticket->id} من {$oldStatus} إلى {$ticket->status_label}",
                $ticket->id,
                $ticket->created_by
            ));
        }

        $message = "قام {$admin->fullname} باستلام التذكرة #{$ticket->id}";
        foreach ($this->getUsersAllowedToView($ticket) as $other) {
            if ($other->id !== $admin->id) {
                $this->notify($other, new RealTimeNotification($message, $ticket->id, $other->id));
            }
        }
        $this->notifyDepartmentChats($ticket, $message);
    }

    /**
     * Admin marks the ticket finished (waiting for the creator to confirm).
     */
    public function markFinished(User $admin, Ticket $ticket): void
    {
        $this->authorizeUpdate($admin, $ticket);

        $oldStatus = $ticket->status_label;
        $ticket->update(['status' => 'finished']);
        $newStatus = $ticket->status_label;

        if ($ticket->assignedUsers->isEmpty()) {
            $ticket->assignedUsers()->syncWithoutDetaching([$admin->id]);

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $admin->id,
                'content' => "تم تعيين التذكرة تلقائياً إلى {$admin->fullname} عند إغلاقها (إنهاء المهمة)",
            ]);

            $ticket->load('assignedUsers');
        }

        $this->notify($ticket->creator, new RealTimeNotification(
            "تم تغيير حالة التذكرة #{$ticket->id} من {$oldStatus} إلى {$newStatus}",
            $ticket->id,
            $ticket->created_by
        ));

        foreach ($this->getUsersAllowedToView($ticket) as $other) {
            if ($other->id !== $admin->id) {
                $this->notify($other, new RealTimeNotification(
                    "تم إنهاء التذكرة #{$ticket->id} وتعيينها لـ {$admin->fullname}",
                    $ticket->id,
                    $other->id
                ));
            }
        }

        $this->notifyDepartmentChats($ticket, "تم تغيير حالة التذكرة #{$ticket->id} إلى منتهية بواسطة {$admin->fullname}");

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'content' => "تم تغيير الحالة من {$oldStatus} إلى {$newStatus}",
        ]);
    }

    public function canView(User $user, Ticket $ticket): bool
    {
        return $user->can('view', $ticket);
    }

    private function authorizeUpdate(User $user, Ticket $ticket): void
    {
        if (!$user->can('update', $ticket)) {
            throw new AuthorizationException();
        }
    }

    private function notifyDepartmentChats(Ticket $ticket, string $message): void
    {
        try {
            $ticket->category?->department?->notifyTelegramChats($ticket, $message);
        } catch (\Throwable $e) {
            logger()->warning('Department chat notify failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Same behaviour as Controller::safeNotify: never let a channel failure break the action.
     */
    private function notify($notifiable, Notification $notification): void
    {
        if (!$notifiable) {
            return;
        }

        try {
            $notifiable->notify($notification);
        } catch (CouldNotSendNotification $e) {
            logger()->warning('Telegram notification failed', [
                'notifiable' => get_class($notifiable) . ':' . ($notifiable->id ?? '?'),
                'notification' => get_class($notification),
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            logger()->error('Notification failed', [
                'error' => $e->getMessage(),
                'notifiable' => get_class($notifiable),
                'notification' => get_class($notification),
            ]);
        }
    }
}
