<?php

namespace App\Notifications\Telegram;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;
use App\Models\Ticket;
use App\Models\User;

class NewMessageNotification extends Notification
{
    use Queueable;

    protected Ticket $ticket;
    protected User $fromUser;
    protected string $text;

    public function __construct(Ticket $ticket, User $fromUser, string $text)
    {
        $this->ticket = $ticket;
        $this->fromUser = $fromUser;
        $this->text = $text;
    }

    public function via($notifiable)
    {
        return [TelegramChannel::class];
    }

    public function toTelegram($notifiable)
    {
        $ticketLink = route('tickets.show', $this->ticket->id);
        $name = $notifiable->fullname ?? $notifiable->name ?? 'المستخدم';

        $message = "{$this->text}\n\n";
        $message .= "لعرض التذكرة: [رابط التذكرة]({$ticketLink})";

        return TelegramMessage::create()
            ->content($message);
    }
}
