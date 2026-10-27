<?php

namespace Tests\Feature;

use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsTickets;
use Tests\TestCase;

/**
 * The web controllers now delegate to TicketService; these guard the HTTP behaviour.
 */
class TicketWebFlowTest extends TestCase
{
    use RefreshDatabase, BuildsTickets;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('public');
        $this->setUpTicketWorld();
    }

    public function test_member_creates_ticket_with_compressed_photo(): void
    {
        $member = $this->member();

        $response = $this->actingAs($member)->post(route('tickets.store'), [
            'title' => 'Monitor',
            'description' => 'Flickers',
            'office_location' => 'Lab',
            'category_id' => $this->category->id,
            'attachments' => [UploadedFile::fake()->image('photo.jpg', 1600, 1200)->size(900)],
        ]);

        $ticket = Ticket::firstOrFail();
        $response->assertRedirect(route('tickets.show', $ticket));
        $this->assertSame(1, $ticket->attachments()->count());
    }

    public function test_create_redirects_back_with_error_when_finished_ticket_pending(): void
    {
        $member = $this->member();
        $this->ticketFor($member, 'finished');

        $this->actingAs($member)->from(route('tickets.create'))->post(route('tickets.store'), [
            'title' => 'x', 'description' => 'y', 'office_location' => 'z', 'category_id' => $this->category->id,
        ])->assertRedirect(route('tickets.create'))->assertSessionHas('error');

        $this->assertSame(1, Ticket::count());
    }

    public function test_reply_and_forbidden_reply(): void
    {
        $member = $this->member();
        $ticket = $this->ticketFor($member);

        $this->actingAs($member)->post(route('messages.store'), ['ticket_id' => $ticket->id, 'content' => 'any news?'])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame(1, $ticket->messages()->count());

        $this->actingAs($this->member())->post(route('messages.store'), ['ticket_id' => $ticket->id, 'content' => 'hijack'])
            ->assertForbidden();
    }

    public function test_creator_confirms_and_admin_marks_finished(): void
    {
        $member = $this->member();
        $admin = $this->departmentAdmin();
        $ticket = $this->ticketFor($member, 'in_progress');

        $this->actingAs($admin)->put(route('admin.tickets.markAsFinishedTicket', $ticket))->assertRedirect(route('tickets.show', $ticket->id));
        $this->assertSame('finished', $ticket->fresh()->status);

        $this->actingAs($member)->put(route('tickets.finish', $ticket))->assertRedirect();
        $this->assertSame('closed', $ticket->fresh()->status);
    }
}
