<?php

namespace Tests\Feature;

use App\Notifications\RealTimeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\Concerns\BuildsTickets;
use Tests\TestCase;

class NotificationChannelsTest extends TestCase
{
    use RefreshDatabase, BuildsTickets;

    public function test_web_push_is_sent_before_telegram(): void
    {
        $this->setUpTicketWorld();
        $user = $this->member(['telegram_number' => '123']);
        $user->updatePushSubscription('https://fcm.googleapis.com/fcm/send/x', 'k', 't', 'aes128gcm');

        $channels = array_values((new RealTimeNotification('hi', 1, $user->id))->via($user));

        $this->assertLessThan(
            array_search(TelegramChannel::class, $channels),
            array_search(WebPushChannel::class, $channels)
        );
    }
}
