<?php

namespace App\Notifications\Admin;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class UserProfileUpdatedNotification extends Notification implements ShouldQueue

{
    use Queueable;

    protected User $updatedUser;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $updatedUser)
    {
        $this->updatedUser = $updatedUser;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [TelegramChannel::class];
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram(object $notifiable)
    {
        $userName = $this->updatedUser->fullname;
        // يمكنك تغيير الرابط ليوجه إلى صفحة عرض المستخدم في لوحة التحكم
        $url = route('admin.users.edit', $this->updatedUser);

        return TelegramMessage::create()
            ->content("📢 *تحديث ملف شخصي*\n\nتم تحديث الملف الشخصي للمستخدم: *{$userName}*.")
            ->button('عرض لوحة التحكم', $url);
    }
}