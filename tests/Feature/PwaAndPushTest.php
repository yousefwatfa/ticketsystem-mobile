<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTickets;
use Tests\TestCase;

class PwaAndPushTest extends TestCase
{
    use RefreshDatabase, BuildsTickets;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTicketWorld();
    }

    public function test_manifest_is_valid_and_rtl(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true);

        $this->assertSame('rtl', $manifest['dir']);
        $this->assertSame('standalone', $manifest['display']);
        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    public function test_install_page_is_public(): void
    {
        $this->get('/install')->assertOk()->assertSee('تثبيت التطبيق');
    }

    public function test_user_can_subscribe_and_unsubscribe_push(): void
    {
        $user = $this->member();
        $endpoint = 'https://fcm.googleapis.com/fcm/send/abc';

        $this->actingAs($user)->postJson('/push-subscriptions', [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'pub-key', 'auth' => 'auth-token'],
            'contentEncoding' => 'aes128gcm',
        ])->assertOk();

        $this->assertSame(1, $user->pushSubscriptions()->count());

        $this->actingAs($user)->deleteJson('/push-subscriptions', ['endpoint' => $endpoint])->assertOk();

        $this->assertSame(0, $user->pushSubscriptions()->count());
    }

    public function test_push_routes_require_login(): void
    {
        $this->postJson('/push-subscriptions', [])->assertUnauthorized();
    }
}
