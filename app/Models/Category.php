<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Department;

class Category extends Model
{
    protected $fillable = ['name','department_id'];

    public function tickets() {
        return $this->hasMany(Ticket::class);
    }

    public function department() {
        return $this->belongsTo(Department::class);
    }

    public function mainDepartment() {
        return $this->hasOneThrough(MainDepartment::class, Department::class, 'id', 'id', 'department_id', 'main_department_id');
    }
}

