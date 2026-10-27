<?php

namespace App\Notifications\Telegram;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class AccountLinked extends Notification
{
    public function via($notifiable)
    {
        return [TelegramChannel::class];
    }

    public function toTelegram($notifiable)
    {
        $name = $notifiable->fullname ?? $notifiable->name ?? 'المستخدم';

        return TelegramMessage::create()
            ->content("✅ تم ربط حسابك بنجاح\n\nمرحبًا {$name}, تم ربط حساب تيليجرام بهذا النظام بنجاح.");
    }
}
