<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A phone running the native app, registered for Expo push notifications.
 */
class DeviceToken extends Model
{
    protected $fillable = ['user_id', 'token', 'platform', 'device_name', 'personal_access_token_id', 'last_used_at'];

    protected $casts = ['last_used_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
