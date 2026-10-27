<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminRole  = \App\Models\Role::firstOrCreate(['name' => 'Admin']);
        $memberRole = \App\Models\Role::firstOrCreate(['name' => 'Member']);
        $viewerRole = \App\Models\Role::firstOrCreate(['name' => 'Viewer']);


       \App\Models\User::updateOrCreate(
        ['username' => 'admin'],
        [
            'fullname' => 'System Admin',
            'email'    => 'admin@admin.com',
            'password' => bcrypt('password'),
            'role_id'  => $adminRole->id,
        ]
    );

    // $mainTech = \App\Models\MainDepartment::create(['name' => 'قطاع التكنولوجيا']);
    // $mainFinance = \App\Models\MainDepartment::create(['name' => 'قطاع المالية']);


    // $techDept = \App\Models\Department::create([
    //     'name' => 'الدعم الفني',
    //     'main_department_id' => $mainTech->id
    // ]);

    // $financeDept = \App\Models\Department::create([
    //     'name' => 'الحسابات',
    //     'main_department_id' => $mainFinance->id
    // ]);


    // \App\Models\Category::create([
    //     'name' => 'Technical Support',
    //     'department_id' => $techDept->id
    // ]);

    //     \App\Models\Category::create([
    //         'name' => 'Billing',
    //         'department_id' => $financeDept->id
    //     ]);
    }
}
