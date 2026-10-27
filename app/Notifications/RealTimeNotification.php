<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use App\Notifications\Channels\ExpoPushChannel;

class RealTimeNotification extends Notification implements ShouldBroadcastNow{
    use Queueable;

    public $message;
    public $ticketId;
    public $userId;

    public function __construct($message, $ticketId, $userId)
    {
        $this->message = $message;
        $this->ticketId = $ticketId;
        $this->userId = $userId;
    }

    public function via($notifiable)
    {
        $channels = ['database', 'broadcast'];

        // phones/desktops that enabled push from the profile page (PWA).
        // Added before Telegram: a failing channel stops the ones after it,
        // and Telegram fails for stale chat ids or a missing token.
        if (method_exists($notifiable, 'pushSubscriptions') && $notifiable->pushSubscriptions()->exists()) {
            $channels[] = WebPushChannel::class;
        }

        // native app (Expo push)
        if (method_exists($notifiable, 'deviceTokens') && $notifiable->deviceTokens()->exists()) {
            $channels[] = ExpoPushChannel::class;
        }

        // user object may have telegram_number property
        if (!empty($notifiable->telegram_number)) {
            $channels[] = TelegramChannel::class;
        }

        // also support objects that implement routeNotificationForTelegram (e.g. department chat model)
        if (method_exists($notifiable, 'routeNotificationForTelegram')) {
            $route = $notifiable->routeNotificationForTelegram($this);
            if (!empty($route)) {
                $channels[] = TelegramChannel::class;
            }
        }

        return array_unique($channels);
    }

    public function toExpoPush($notifiable): array
    {
        return [
            'title' => 'الدعم الفني' . ($this->ticketId ? " - تذكرة #{$this->ticketId}" : ''),
            'body' => $this->message,
            // the app opens the ticket screen from this
            'data' => ['ticket_id' => $this->ticketId],
        ];
    }

    public function toWebPush($notifiable, $notification)
    {
        $isAdmin = $notifiable->role?->name === 'Admin';
        $path = $this->ticketId
            ? ($isAdmin ? "/admin/tickets/{$this->ticketId}" : "/tickets/{$this->ticketId}")
            : '/dashboard';

        return (new WebPushMessage)
            ->title('الدعم الفني' . ($this->ticketId ? " - تذكرة #{$this->ticketId}" : ''))
            ->body($this->message)
            ->icon('/icons/icon-192.png')
            ->tag($this->ticketId ? "ticket-{$this->ticketId}" : 'general')
            ->data(['url' => $path])
            ->options(['TTL' => 86400, 'urgency' => 'high']);
    }

    public function toTelegram($notifiable)
    {
        $message = TelegramMessage::create()
            ->content($this->message)
            ->button('عرض التذكرة', route('tickets.show', $this->ticketId));

        // Action buttons only in private chats with users (department groups: notification only).
        // They are handled by TelegramBotService through telegram:poll.
        if ($notifiable instanceof \App\Models\User && $this->ticketId) {
            $ticket = \App\Models\Ticket::find($this->ticketId);

            if ($ticket && $ticket->status === 'finished' && $ticket->created_by === $notifiable->id) {
                $message->buttonWithCallback('تأكيد الحل', "c:{$ticket->id}")
                    ->buttonWithCallback('المشكلة مستمرة', "nf:{$ticket->id}");
            } elseif ($ticket && $ticket->status !== 'closed') {
                $message->buttonWithCallback('تفاصيل / رد', "t:{$ticket->id}");
            }
        }

        return $message;
    }

    public function toArray($notifiable)
    {
        return [
            'message' => $this->message,
            'ticket_id' => $this->ticketId,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'message' => $this->message,
            'ticket_id' => $this->ticketId,
        ]);
    }
    public function broadcastOn()
    {
        return new PrivateChannel('App.Models.User.' . $this->userId);
    }

}
