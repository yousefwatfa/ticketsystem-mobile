<?php

namespace App\Notifications\Telegram;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;
use App\Models\Ticket;

class TicketStatusNotification extends Notification
{
    use Queueable;

    protected Ticket $ticket;
    protected string $oldStatus;
    protected string $newStatus;

    public function __construct(Ticket $ticket, string $oldStatus, string $newStatus)
    {
        $this->ticket = $ticket;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    public function via($notifiable)
    {
        return [TelegramChannel::class];
    }

    public function toTelegram($notifiable)
    {
        $ticketLink = route('tickets.show', $this->ticket->id);
        $name = $notifiable->fullname ?? $notifiable->name ?? 'المستخدم';

        $message = "🔄 تم تغيير حالة التذكرة\n\n";
        $message .= "مرحبًا {$name}!\n";
        $message .= "التذكرة رقم: #{$this->ticket->id}\n";
        $message .= "الحالة السابقة: {$this->oldStatus}\n";
        $message .= "الحالة الجديدة: {$this->newStatus}\n";
        $message .= "لعرض التفاصيل: [رابط التذكرة]({$ticketLink})";

        return TelegramMessage::create()
            ->content($message);
    }
}
