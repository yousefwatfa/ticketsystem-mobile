<?php

namespace App\Notifications\Telegram;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;
use App\Models\Ticket;
use App\Models\User;

class TicketAssignedNotification extends Notification
{
    use Queueable;

    protected $ticket;
    protected $assignedUser;

    public function __construct(Ticket $ticket, User $assignedUser)
    {
        $this->ticket = $ticket;
        $this->assignedUser = $assignedUser;
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
        $assignedUserName = $this->assignedUser->fullname ?? $this->assignedUser->name ?? 'المستخدم';

        $message = "👨‍💻 تم تعيين تذكرة لك\n\n";
        $message .= "مرحبًا {$assignedUserName}! \n";
        $message .= "تم تعيين التذكرة رقم: #{$ticketId} لك.\n";
        $message .= "الموضوع: {$ticketSubject}\n";
        $message .= "لعرض التفاصيل: [رابط التذكرة]({$ticketLink})";

        return TelegramMessage::create()
            ->content($message);
    }
}
