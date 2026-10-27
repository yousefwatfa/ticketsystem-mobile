<?php

namespace App\Notifications\Telegram;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;
use App\Models\Ticket;

class NewTicketNotification extends Notification
{
    use Queueable;

    protected $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function via($notifiable)
    {
        return [TelegramChannel::class];
    }

    public function toTelegram($notifiable)
    {
        $ticketId = $this->ticket->id;
        $ticketSubject = $this->ticket->subject;
        $ticketLink = route('tickets.show', $ticketId);
        $name = $notifiable->fullname ?? $notifiable->name ?? 'المستخدم';

        $message = "🔔 تذكرة جديدة تم إنشاؤها\n\n";
        $message .= "مرحبًا {$name}! \n";
        $message .= "تم إنشاء تذكرة جديدة بالرقم: #{$ticketId}\n";
        $message .= "الموضوع: {$ticketSubject}\n";
        $message .= "لعرض التفاصيل: [رابط التذكرة]({$ticketLink})";

        return TelegramMessage::create()
            ->content($message);
    }
}
