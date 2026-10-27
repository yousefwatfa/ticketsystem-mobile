<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NationalIdLog extends Model
{
    protected $fillable = ['user_id', 'old_id', 'new_id'];
}
