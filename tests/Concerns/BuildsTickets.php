<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\Department;
use App\Models\MainDepartment;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;

trait BuildsTickets
{
    protected Role $memberRole;
    protected Role $adminRole;
    protected Category $category;
    protected Department $department;

    protected function setUpTicketWorld(): void
    {
        $this->memberRole = Role::create(['name' => 'Member']);
        $this->adminRole = Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Viewer']);

        $main = MainDepartment::create(['name' => 'Computer Center']);
        $this->department = Department::create(['name' => 'IT', 'main_department_id' => $main->id]);
        $this->category = Category::create(['name' => 'Printer', 'department_id' => $this->department->id]);
    }

    /** Admin who belongs to the ticket's department (receives notifications, may update). */
    protected function departmentAdmin(array $attributes = []): User
    {
        $admin = User::factory()->create(['role_id' => $this->adminRole->id] + $attributes);
        $admin->departments()->attach($this->department->id);

        return $admin;
    }

    protected function member(array $attributes = []): User
    {
        return User::factory()->create(['role_id' => $this->memberRole->id] + $attributes);
    }

    protected function rootAdmin(array $attributes = []): User
    {
        return User::factory()->create(['role_id' => $this->adminRole->id, 'is_root' => true] + $attributes);
    }

    protected function ticketFor(User $creator, string $status = 'open'): Ticket
    {
        return Ticket::create([
            'title' => 'Printer jam',
            'description' => 'Paper stuck',
            'office_location' => 'Room 4',
            'category_id' => $this->category->id,
            'created_by' => $creator->id,
            'status' => $status,
        ]);
    }
}
