<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Note extends Model
{
    protected $fillable = ['body','user_id'];


    public function noteable(): MorphTo {
        return $this->morphTo();
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
