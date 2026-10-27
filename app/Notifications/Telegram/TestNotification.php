<?php

namespace App\Notifications\Telegram;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class TestNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return [TelegramChannel::class];
    }

    public function toTelegram(object $notifiable)
    {
        $name = $notifiable->fullname ?? $notifiable->name ?? 'المستخدم';

        return TelegramMessage::create()
            ->content("✅ رسالة اختبار من نظام التذاكر\n\nمرحبًا {$name}! هذه رسالة اختبارية لإعداد إشعارات تيليجرام.\nإذا استلمت هذه الرسالة فالإشعارات تعمل.");
    }
}
