<?php

namespace Tests\Feature\Api;

use App\Models\DeviceToken;
use App\Models\Ticket;
use App\Notifications\RealTimeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsTickets;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase, BuildsTickets;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->setUpTicketWorld();
    }

    // ---------------------------------------------------------------- auth

    public function test_local_user_logs_in_and_gets_a_token(): void
    {
        $user = $this->member(['auth_type' => 'normal']);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => $user->username, 'password' => 'password', 'device_name' => 'Pixel 8',
        ])->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'fullname', 'role', 'is_admin']]);

        $this->withToken($response->json('token'))->getJson('/api/v1/me')
            ->assertOk()->assertJsonPath('data.username', $user->username);
    }

    public function test_wrong_password_is_rejected_with_arabic_message(): void
    {
        $user = $this->member(['auth_type' => 'normal']);

        $this->postJson('/api/v1/auth/login', ['username' => $user->username, 'password' => 'nope'])
            ->assertStatus(422)->assertJsonValidationErrors('username');
    }

    public function test_api_requires_token(): void
    {
        $this->getJson('/api/v1/tickets')->assertUnauthorized();
        $this->get('/api/v1/tickets')->assertUnauthorized()->assertHeader('content-type', 'application/json');
    }

    public function test_logout_revokes_token_and_device(): void
    {
        $user = $this->member(['auth_type' => 'normal']);
        $token = $this->postJson('/api/v1/auth/login', ['username' => $user->username, 'password' => 'password'])->json('token');

        $this->withToken($token)->postJson('/api/v1/devices', ['token' => 'ExponentPushToken[abc]', 'platform' => 'android'])->assertCreated();
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertSame(0, DeviceToken::count());
        $this->assertSame(0, $user->tokens()->count());
    }

    // ---------------------------------------------------------------- staff tickets

    public function test_member_lists_only_own_tickets_with_counts(): void
    {
        $member = $this->member();
        $this->ticketFor($member, 'open');
        $this->ticketFor($member, 'finished');
        $this->ticketFor($this->member());
        Sanctum::actingAs($member);

        $response = $this->getJson('/api/v1/tickets')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('counts.all', 2)
            ->assertJsonPath('counts.finished', 1);

        $byStatus = collect($response->json('data'))->keyBy('status');
        $this->assertTrue($byStatus['finished']['can']['confirm']);
        $this->assertFalse($byStatus['open']['can']['confirm']);

        $this->getJson('/api/v1/tickets?status=open')->assertJsonCount(1, 'data');
    }

    public function test_member_creates_ticket_with_photo(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->member());

        $this->post('/api/v1/tickets', [
            'title' => 'Printer', 'description' => 'Jam', 'office_location' => 'ER',
            'category_id' => $this->category->id,
            'attachments' => [UploadedFile::fake()->image('p.jpg')],
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonCount(1, 'data.attachments')
            ->assertJsonPath('data.attachments.0.is_image', true);
    }

    public function test_create_returns_409_while_finished_ticket_pending(): void
    {
        $member = $this->member();
        $this->ticketFor($member, 'finished');
        Sanctum::actingAs($member);

        $this->postJson('/api/v1/tickets', [
            'title' => 'x', 'description' => 'y', 'office_location' => 'z', 'category_id' => $this->category->id,
        ])->assertStatus(409)->assertJsonPath('code', 'finished_ticket_pending');
    }

    public function test_ticket_details_reply_and_privacy(): void
    {
        Notification::fake();
        $member = $this->member();
        $ticket = $this->ticketFor($member);

        Sanctum::actingAs($member);
        $this->postJson("/api/v1/tickets/{$ticket->id}/messages", ['content' => 'any news?'])
            ->assertCreated()->assertJsonPath('data.is_mine', true);
        $this->getJson("/api/v1/tickets/{$ticket->id}")
            ->assertOk()->assertJsonCount(1, 'data.messages')->assertJsonPath('data.can.reply', true);

        Sanctum::actingAs($this->member());
        $this->getJson("/api/v1/tickets/{$ticket->id}")->assertForbidden();
        $this->postJson("/api/v1/tickets/{$ticket->id}/messages", ['content' => 'x'])->assertForbidden();
    }

    public function test_creator_confirms_or_reports_not_fixed(): void
    {
        Notification::fake();
        $member = $this->member();
        $a = $this->ticketFor($member, 'finished');
        $b = $this->ticketFor($member, 'finished');
        Sanctum::actingAs($member);

        $this->postJson("/api/v1/tickets/{$a->id}/confirm")->assertOk()->assertJsonPath('data.status', 'closed');
        $this->postJson("/api/v1/tickets/{$b->id}/not-fixed")->assertOk()->assertJsonPath('data.status', 'in_progress');
        $this->postJson("/api/v1/tickets/{$a->id}/confirm")->assertStatus(422);
    }

    // ---------------------------------------------------------------- admin

    public function test_admin_sees_department_tickets_and_takes_and_finishes(): void
    {
        Notification::fake();
        $admin = $this->departmentAdmin();
        $ticket = $this->ticketFor($this->member());
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/tickets')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.can.take', true);

        $this->postJson("/api/v1/admin/tickets/{$ticket->id}/take")->assertOk()->assertJsonPath('data.status', 'in_progress');
        $this->postJson("/api/v1/admin/tickets/{$ticket->id}/finish")->assertOk()->assertJsonPath('data.status', 'finished');
    }

    public function test_admin_search_by_id_and_title(): void
    {
        $admin = $this->departmentAdmin();
        $ticket = $this->ticketFor($this->member());
        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/admin/tickets?search={$ticket->id}")->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/admin/tickets?search=Printer')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/admin/tickets?search=nothing-like-this')->assertJsonCount(0, 'data');
    }

    public function test_admin_outside_department_sees_nothing_and_cannot_act(): void
    {
        $outsider = \App\Models\User::factory()->create(['role_id' => $this->adminRole->id]);
        $ticket = $this->ticketFor($this->member());
        Sanctum::actingAs($outsider);

        $this->getJson('/api/v1/admin/tickets')->assertOk()->assertJsonCount(0, 'data');
        $this->postJson("/api/v1/admin/tickets/{$ticket->id}/take")->assertForbidden();
    }

    public function test_members_cannot_use_admin_endpoints(): void
    {
        Sanctum::actingAs($this->member());

        $this->getJson('/api/v1/admin/tickets')->assertForbidden();
    }

    // ---------------------------------------------------------------- notifications & push

    public function test_notifications_list_and_mark_read(): void
    {
        $member = $this->member();
        $member->notifyNow(new RealTimeNotification('hello #1', 1, $member->id), ['database']);
        Sanctum::actingAs($member);

        $this->getJson('/api/v1/notifications')->assertOk()->assertJsonPath('unread', 1)->assertJsonPath('data.0.ticket_id', 1);
        $this->postJson('/api/v1/notifications/read')->assertOk();
        $this->getJson('/api/v1/notifications')->assertJsonPath('unread', 0);
    }

    public function test_invalid_push_token_is_rejected(): void
    {
        Sanctum::actingAs($this->member());

        $this->postJson('/api/v1/devices', ['token' => 'not-an-expo-token'])->assertStatus(422);
    }

    public function test_expo_push_is_sent_and_dead_tokens_are_removed(): void
    {
        Http::fake([
            'exp.host/*' => Http::response(['data' => [
                ['status' => 'ok', 'id' => '1'],
                ['status' => 'error', 'details' => ['error' => 'DeviceNotRegistered']],
            ]]),
        ]);
        $member = $this->member();
        $member->deviceTokens()->create(['token' => 'ExponentPushToken[good]']);
        $member->deviceTokens()->create(['token' => 'ExponentPushToken[dead]']);

        $member->notifyNow(new RealTimeNotification('رد جديد على تذكرة #5', 5, $member->id), [\App\Notifications\Channels\ExpoPushChannel::class]);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'exp.host')
            && $r[0]['to'] === 'ExponentPushToken[good]'
            && $r[0]['data']['ticket_id'] === 5);
        $this->assertSame(['ExponentPushToken[good]'], DeviceToken::pluck('token')->all());
    }
}
