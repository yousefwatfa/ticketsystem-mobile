<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    protected $fillable = ['file_path','file_type', 'file_name'];

    public function attachable(): MorphTo {
        return $this->morphTo();
    }
}
