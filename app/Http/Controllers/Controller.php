<?php

namespace App\Http\Controllers;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use Illuminate\Log\LogManager;

abstract class Controller
{
    /**
     * Send a notification, but swallow Telegram failures.
     *
     * Many users or chats may have invalid/expired IDs; the third-party
     * channel will throw a CouldNotSendNotification exception which would
     * otherwise bubble up and crash the request.  We catch it here and
     * record a warning instead, allowing the process to continue.
     */
    protected function safeNotify($notifiable, Notification $notification)
    {
        try {
            $notifiable->notify($notification);
        } catch (CouldNotSendNotification $e) {
            // log a warning but do NOT clear the user's telegram_number
            logger()->warning('Telegram notification failed', [
                'notifiable'   => get_class($notifiable) . ':' . ($notifiable->id ?? '?'),
                'notification' => get_class($notification),
                'message'      => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            // fallback for anything else
            logger()->error('Notification failed', [
                'error' => $e->getMessage(),
                'notifiable' => get_class($notifiable),
                'notification' => get_class($notification),
            ]);
        }
    }
}
