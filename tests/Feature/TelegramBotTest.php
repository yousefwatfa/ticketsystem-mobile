<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsTickets;
use Tests\TestCase;

class TelegramBotTest extends TestCase
{
    use RefreshDatabase, BuildsTickets;

    private const CHAT = 5550001;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram-bot-api.token' => 'test-token']);
        Notification::fake();
        Storage::fake('public');
        $this->setUpTicketWorld();

        Http::fake([
            '*/getFile' => Http::response(['ok' => true, 'result' => ['file_id' => 'f1', 'file_size' => 1000, 'file_path' => 'photos/p.jpg']]),
            '*/file/bot*' => Http::response('jpeg-bytes'),
            '*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]]),
        ]);
    }

    private function bot(): TelegramBotService
    {
        return app(TelegramBotService::class);
    }

    private function text(string $text, string $chatType = 'private'): array
    {
        return ['update_id' => 1, 'message' => ['message_id' => 1, 'chat' => ['id' => self::CHAT, 'type' => $chatType], 'text' => $text]];
    }

    private function tap(string $data): array
    {
        return ['update_id' => 2, 'callback_query' => [
            'id' => 'cb1', 'data' => $data,
            'message' => ['message_id' => 9, 'chat' => ['id' => self::CHAT, 'type' => 'private']],
        ]];
    }

    private function sentTexts(): array
    {
        return Http::recorded()
            ->filter(fn ($pair) => str_ends_with($pair[0]->url(), '/sendMessage'))
            ->map(fn ($pair) => $pair[0]['text'])
            ->values()->all();
    }

    public function test_unlinked_chat_gets_its_chat_id_and_link_instructions(): void
    {
        $this->bot()->handle($this->text('hello'));

        $this->assertStringContainsString((string) self::CHAT, $this->sentTexts()[0]);
    }

    public function test_start_with_token_links_the_account_once(): void
    {
        $user = $this->member(['telegram_link_token' => 'abc123']);

        $this->bot()->handle($this->text('/start abc123'));

        $user->refresh();
        $this->assertSame((string) self::CHAT, $user->telegram_number);
        $this->assertNotNull($user->telegram_linked_at);
        $this->assertNull($user->telegram_link_token);
    }

    public function test_group_chats_are_ignored(): void
    {
        $this->member(['telegram_number' => (string) self::CHAT]);

        $this->bot()->handle($this->text('/new', 'group'));

        Http::assertNothingSent();
    }

    public function test_full_new_ticket_flow_creates_ticket_with_photo(): void
    {
        $user = $this->member(['telegram_number' => (string) self::CHAT]);
        $bot = $this->bot();

        $bot->handle($this->text('/new'));
        $bot->handle($this->tap("cat:{$this->category->id}"));
        $bot->handle($this->text('Ward 3'));
        $bot->handle($this->text('Printer offline'));
        $bot->handle($this->text('It shows error 50'));
        $bot->handle(['update_id' => 3, 'message' => [
            'message_id' => 5, 'chat' => ['id' => self::CHAT, 'type' => 'private'],
            'photo' => [['file_id' => 'small', 'file_size' => 100], ['file_id' => 'big', 'file_size' => 900000]],
        ]]);
        $bot->handle($this->tap('skip:photo'));
        $bot->handle($this->tap('new:send'));

        $ticket = Ticket::where('created_by', $user->id)->first();
        $this->assertNotNull($ticket);
        $this->assertSame('Printer offline', $ticket->title);
        $this->assertSame('Ward 3', $ticket->office_location);
        $this->assertSame(1, $ticket->attachments()->count());
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/getFile') && $r['file_id'] === 'big');

        // tapping "send" again must not create a duplicate
        $bot->handle($this->tap('new:send'));
        $this->assertSame(1, Ticket::where('created_by', $user->id)->count());
    }

    public function test_creator_confirms_finished_ticket_from_button(): void
    {
        $user = $this->member(['telegram_number' => (string) self::CHAT]);
        $ticket = $this->ticketFor($user, 'finished');

        $this->bot()->handle($this->tap("c:{$ticket->id}"));

        $this->assertSame('closed', $ticket->fresh()->status);
    }

    public function test_reply_to_notification_adds_message_to_ticket(): void
    {
        $user = $this->member(['telegram_number' => (string) self::CHAT]);
        $ticket = $this->ticketFor($user);

        $this->bot()->handle(['update_id' => 4, 'message' => [
            'message_id' => 7, 'chat' => ['id' => self::CHAT, 'type' => 'private'],
            'text' => 'Still broken',
            'reply_to_message' => ['message_id' => 3, 'text' => "رد جديد على تذكرة #{$ticket->id} من الدعم"],
        ]]);

        $this->assertSame('Still broken', $ticket->messages()->first()->content);
    }

    public function test_member_cannot_take_or_finish_tickets(): void
    {
        $user = $this->member(['telegram_number' => (string) self::CHAT]);
        $ticket = $this->ticketFor($this->member());

        $this->bot()->handle($this->tap("f:{$ticket->id}"));

        $this->assertSame('open', $ticket->fresh()->status);
    }

    public function test_admin_takes_ticket_from_button(): void
    {
        $admin = $this->rootAdmin(['telegram_number' => (string) self::CHAT]);
        $ticket = $this->ticketFor($this->member());

        $this->bot()->handle($this->tap("tk:{$ticket->id}"));

        $ticket->refresh();
        $this->assertSame('in_progress', $ticket->status);
        $this->assertTrue($ticket->assignedUsers->contains($admin->id));
    }
}
