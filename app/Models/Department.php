<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use App\Models\MainDepartment;
use App\Models\User;
use App\Models\Ticket;




class Department extends Model
{
  protected $fillable = ['name','main_department_id'];

    public function categories() {
        return $this->hasMany(Category::class);
    }

    public function mainDepartment() {
        return $this->belongsTo(MainDepartment::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Telegram chats associated with this department.
     * A department can have multiple Telegram group chat IDs.
     */
    public function telegramChats()
    {
        return $this->hasMany(\App\Models\DepartmentTelegramChat::class);
    }

    /**
     * Send plain real-time notification message to all applicable telegram chats
     * linked to this department. Filters by hospital if chat has hospital_id set.
     */
    public function notifyTelegramChats(Ticket $ticket, string $message)
    {
        $chats = $this->telegramChats;
        $creatorHospitalIds = $ticket->creator->hospitals()->pluck('hospitals.id')->toArray();

        foreach ($chats as $chat) {
            if ($chat->hospital_id && !in_array($chat->hospital_id, $creatorHospitalIds)) {
                continue;
            }
            try {
                $chat->notify(new \App\Notifications\RealTimeNotification($message, $ticket->id, 0));
            } catch (\NotificationChannels\Telegram\Exceptions\CouldNotSendNotification $e) {
                logger()->warning('Department chat Telegram failed', ['chat_id' => $chat->chat_id, 'error' => $e->getMessage()]);
            } catch (\Exception $e) {
                logger()->error('Department chat notification error', ['chat_id' => $chat->chat_id, 'error' => $e->getMessage()]);
            }
        }
    }
}
