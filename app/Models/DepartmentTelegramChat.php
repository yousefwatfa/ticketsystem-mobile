<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Notifications\Notifiable;

class DepartmentTelegramChat extends Model
{
    use Notifiable;

    protected $table = 'department_telegram_chats';
    protected $fillable = ['department_id', 'chat_id', 'name', 'hospital_id'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Optional hospital filter for this chat.
     */
    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    public function routeNotificationForTelegram($notification = null)
    {
        // return the raw chat id so the telegram channel can deliver messages
        return $this->chat_id;
    }
}
