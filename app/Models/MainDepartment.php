<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Department;
use App\Models\User;

class MainDepartment extends Model
{
    protected $fillable = ['name'];

    public function departments() {
        return $this->hasMany(Department::class);
    }

    public function users()
    {
        return User::whereHas('departments', function ($query) {
            $query->where('main_department_id', $this->id);
        });
    }
}
