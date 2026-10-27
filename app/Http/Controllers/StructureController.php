<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\MainDepartment;

class StructureController extends Controller
{
    public function index()
    {
        $mainDepartments = MainDepartment::with(['departments' => function ($query) {
            $query->orderBy('name');
        }])
            ->orderBy('name')
            ->get();

        $unlinkedDepartments = Department::whereNull('main_department_id')
            ->orderBy('name')
            ->get();

        return view('admin.structure.index', compact('mainDepartments', 'unlinkedDepartments'));
    }
}
