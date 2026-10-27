<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;
use App\Models\Department;
use App\Models\Note;
use App\Models\Hospital;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasPushSubscriptions;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'fullname',
        'email',
        'password',
        'role_id',
        'is_root',
        'auth_type',
        'whatsapp_number',
        'telegram_number',
        'office_location',
        'can_reopen_tickets',
        'can_access_finished_tickets',
        'national_id',
        'classification_name',
        'telegram_link_token',
        'telegram_linked_at',
    ];

    public function role() {
        return $this->belongsTo(Role::class);
    }

    public function tickets() {
        return $this->hasMany(Ticket::class, 'created_by');
    }

    public function assignedTickets() {
        return $this->belongsToMany(Ticket::class, 'ticket_user');
    }

    public function isRoot() {
        return $this->is_root;
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class);
    }
    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function hospitals()
    {
        return $this->belongsToMany(Hospital::class);
    }

    public function receivesBroadcastNotificationsOn()
{
    return 'App.Models.User.' . $this->id;
}

public function nationalIdLogs()
{
    return $this->hasMany(NationalIdLog::class);
}

    /**
     * Return the Telegram chat id for notifications.
     * Used by the Telegram notification channel.
     */
    public function routeNotificationForTelegram($notification = null)
    {
        return $this->telegram_number;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_root' => 'boolean',
            'can_reopen_tickets' => 'boolean',
            'can_access_finished_tickets' => 'boolean',
            'telegram_linked_at' => 'datetime',
        ];
    }
}
