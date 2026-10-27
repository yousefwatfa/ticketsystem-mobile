<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Department;
use App\Models\MainDepartment;
use App\Models\Message;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local demo data for trying the PWA/mobile screens. Never run in production.
 *   php artisan db:seed --class=DemoSeeder
 * Logins (auth_type "normal"): demo.member / demo.admin, password: password
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('DemoSeeder is for local testing only.');
            return;
        }

        $member = Role::firstOrCreate(['name' => 'Member']);
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        Role::firstOrCreate(['name' => 'Viewer']);

        $main = MainDepartment::firstOrCreate(['name' => 'الحاسوب وتكنولوجيا المعلومات']);
        $dept = Department::firstOrCreate(['name' => 'الدعم الفني'], ['main_department_id' => $main->id]);
        $categories = collect(['طابعة', 'شاشة / جهاز', 'شبكة وإنترنت', 'نظام المستشفيات'])
            ->map(fn ($name) => Category::firstOrCreate(['name' => $name], ['department_id' => $dept->id]));

        $memberUser = User::updateOrCreate(['username' => 'demo.member'], [
            'fullname' => 'أحمد الموظف', 'email' => 'demo.member@example.test', 'password' => Hash::make('password'),
            'role_id' => $member->id, 'auth_type' => 'normal', 'national_id' => '123456789', 'whatsapp_number' => '0590000001',
            'office_location' => 'قسم الطوارئ',
        ]);

        $adminUser = User::updateOrCreate(['username' => 'demo.admin'], [
            'fullname' => 'سارة الدعم الفني', 'email' => 'demo.admin@example.test', 'password' => Hash::make('password'),
            'role_id' => $admin->id, 'auth_type' => 'normal', 'national_id' => '987654321', 'whatsapp_number' => '0590000002',
        ]);
        $adminUser->departments()->syncWithoutDetaching([$dept->id]);

        if (Ticket::where('created_by', $memberUser->id)->exists()) {
            return;
        }

        $samples = [
            ['الطابعة لا تطبع', 'تظهر رسالة خطأ ورق عالق في طابعة التمريض', 'open'],
            ['بطء في نظام المستشفيات', 'النظام يتأخر عند فتح ملف المريض', 'in_progress'],
            ['انقطاع الإنترنت في الغرفة 12', 'لا يوجد اتصال منذ الصباح', 'pending'],
            ['تغيير شاشة الاستقبال', 'الشاشة تومض باستمرار', 'closed'],
        ];

        foreach ($samples as $i => [$title, $description, $status]) {
            $ticket = Ticket::create([
                'title' => $title, 'description' => $description, 'office_location' => 'قسم الطوارئ',
                'category_id' => $categories[$i]->id, 'created_by' => $memberUser->id, 'status' => $status,
            ]);

            if ($status !== 'open') {
                $ticket->assignedUsers()->attach($adminUser->id);
                Message::create(['ticket_id' => $ticket->id, 'created_by' => $adminUser->id, 'content' => 'تم استلام التذكرة وسنتابع معك قريباً.']);
            }
        }
    }
}
