<?php

namespace Tests\Feature;

use App\Models\TicketLog;
use App\Models\User;
use App\Notifications\RealTimeNotification;
use App\Services\TicketService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsTickets;
use Tests\TestCase;

class TicketServiceTest extends TestCase
{
    use RefreshDatabase, BuildsTickets;

    private TicketService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('public');
        $this->setUpTicketWorld();
        $this->service = app(TicketService::class);
    }

    public function test_create_stores_ticket_log_attachment_and_notifies(): void
    {
        $member = $this->member();
        $admin = $this->departmentAdmin();
        $outsider = $this->member();

        $ticket = $this->service->create($member, [
            'title' => 'Screen broken',
            'description' => 'No display',
            'office_location' => 'ER',
            'category_id' => $this->category->id,
        ], [['contents' => 'jpeg-bytes', 'name' => 'photo.jpg', 'extension' => 'jpg']]);

        $this->assertSame('open', $ticket->status);
        $this->assertSame(1, $ticket->attachments()->count());
        Storage::disk('public')->assertExists($ticket->attachments()->first()->file_path);
        $this->assertTrue(TicketLog::where('ticket_id', $ticket->id)->exists());
        Notification::assertSentTo($admin, RealTimeNotification::class);
        Notification::assertSentTo($member, RealTimeNotification::class);
        Notification::assertNotSentTo($outsider, RealTimeNotification::class);
    }

    public function test_department_admin_can_take_ticket(): void
    {
        $admin = $this->departmentAdmin();
        $ticket = $this->ticketFor($this->member());

        $this->service->take($admin, $ticket);

        $this->assertTrue($ticket->fresh()->assignedUsers->contains($admin->id));
    }

    public function test_create_is_blocked_while_a_finished_ticket_awaits_confirmation(): void
    {
        $member = $this->member();
        $this->ticketFor($member, 'finished');

        $this->expectException(\DomainException::class);

        $this->service->create($member, [
            'title' => 'x', 'description' => 'y', 'office_location' => 'z', 'category_id' => $this->category->id,
        ]);
    }

    public function test_confirm_close_only_by_creator(): void
    {
        $member = $this->member();
        $ticket = $this->ticketFor($member, 'finished');

        $this->service->confirmClose($member, $ticket);
        $this->assertSame('closed', $ticket->fresh()->status);

        $this->expectException(AuthorizationException::class);
        $this->service->confirmClose($this->member(), $this->ticketFor($member, 'finished'));
    }

    public function test_report_not_fixed_reopens_finished_ticket(): void
    {
        $member = $this->member();
        $ticket = $this->ticketFor($member, 'finished');

        $this->service->reportNotFixed($member, $ticket);

        $this->assertSame('in_progress', $ticket->fresh()->status);
    }

    public function test_take_assigns_admin_and_moves_to_in_progress(): void
    {
        $member = $this->member();
        $admin = $this->rootAdmin();
        $ticket = $this->ticketFor($member);

        $this->service->take($admin, $ticket);

        $ticket->refresh();
        $this->assertSame('in_progress', $ticket->status);
        $this->assertTrue($ticket->assignedUsers->contains($admin->id));
    }

    public function test_take_rejects_users_without_update_permission(): void
    {
        $member = $this->member();
        $ticket = $this->ticketFor($member);

        $this->expectException(AuthorizationException::class);
        $this->service->take($this->member(), $ticket);
    }

    public function test_first_admin_reply_moves_ticket_to_in_progress(): void
    {
        $member = $this->member();
        $admin = $this->rootAdmin();
        $ticket = $this->ticketFor($member);

        $this->service->addMessage($admin, $ticket, 'On my way');

        $this->assertSame('in_progress', $ticket->fresh()->status);
        Notification::assertSentTo($member, RealTimeNotification::class);
    }

    public function test_strangers_cannot_reply(): void
    {
        $ticket = $this->ticketFor($this->member());

        $this->expectException(AuthorizationException::class);
        $this->service->addMessage($this->member(), $ticket, 'hi');
    }

    public function test_mark_finished_assigns_admin_when_unassigned(): void
    {
        $member = $this->member();
        $admin = $this->rootAdmin();
        $ticket = $this->ticketFor($member, 'in_progress');

        $this->service->markFinished($admin, $ticket);

        $ticket->refresh();
        $this->assertSame('finished', $ticket->status);
        $this->assertTrue($ticket->assignedUsers->contains($admin->id));
        $this->assertInstanceOf(User::class, $ticket->assignedUsers->first());
    }
}
