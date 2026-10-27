<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\Attachment;
use App\Models\Department;
use App\Models\Note;



class Ticket extends Model
{
    protected $fillable = [
        'title', 'description', 'status', 'category_id', 'created_by', 'ip_address', 'host_name', 'ip_addresses', 'office_location', 'on_behalf_of'
    ];

    protected $casts = [
        'ip_addresses' => 'array',
    ];

    public function MainDepartment() {
        return $this->category->department->mainDepartment;
    }

    public function category() {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages() {
        return $this->hasMany(Message::class);
    }

    public function assignedUsers() {
        return $this->belongsToMany(User::class, 'ticket_user');
    }

    public function ticketLogs() {
        return $this->hasMany(TicketLog::class)->orderBy('created_at', 'desc');
    }

       public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function internalNotes(): MorphMany {
        return $this->morphMany(Note::class, 'noteable')->latest();
    }

    public function firstAdminReply()
        {
            return $this->hasOne(Message::class, 'ticket_id')
                ->whereHas('user.role', function($query) {
                    $query->where('name', 'Admin');
                })
                ->oldest();
        }

public function onBehalfUser()
{
    return $this->belongsTo(User::class, 'on_behalf_of');
}

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'all' => 'الكل',
            'open' => 'جديد',
            'in_progress' => 'قيد العمل',
            'pending' => 'معلقة',
            'finished'    => 'منتهية',
            'closed' => 'مكتملة',
            default => $this->status,
        };

    }

    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'open' => 'badge-light-primary inset-ring inset-ring-blue-500/20 text-base!', // Blue
            'in_progress' => 'badge-light-warning inset-ring inset-ring-yellow-500/20 text-base!', // Yellow
            'pending' => 'badge-secondary inset-ring inset-ring-gray-500/20 text-base!', // Dark Gray (using secondary for gray, or badge-dark if specifically requested but secondary is gray)
            'finished' => 'badge-light-success inset-ring inset-ring-green-500/20 text-base',
            'closed' => 'badge bg-success text-white',
            default => 'badge-light-secondary inset-ring inset-ring-gray-500/20 text-base!',
        };

    }
}
