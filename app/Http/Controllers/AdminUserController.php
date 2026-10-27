<?php

namespace App\Http\Controllers;

use App\DataTables\UsersDataTable;
use App\Models\Role;
use App\Models\User;
use App\Models\Department;
use App\Models\Hospital;

use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function create()
    {
        $roles = Role::all();
        $departments = Department::all();
        return view('admin.users.create', compact('roles', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users',
            'fullname' => 'required',
            'email' => 'nullable|email|unique:users',
            'role_id' => 'required|exists:roles,id',
            'auth_type' => 'required|in:ldap,normal',
            'password' => 'nullable|min:6|required_if:auth_type,normal',
            'departments' => 'nullable|array',
            'departments.*' => 'exists:departments,id',
        ]);

        $data = $request->except(['departments', 'password']);


        if ($request->auth_type === 'normal') {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
        } else {
            $data['password'] = null;
        }

        $user = User::create($data);

        $role = Role::find($request->role_id);


        if (auth()->user()->is_root) {
            $user->can_reopen_tickets = (strtolower($role->name) === 'admin' && $request->has('can_reopen_tickets')) ? 1 : 0;
            $user->can_access_finished_tickets = (strtolower($role->name) === 'admin' && $request->has('can_access_finished_tickets')) ? 1 : 0;
            $user->save();
        }

        if (strtolower($role->name) === 'admin' || strtolower($role->name) === 'viewer') {
            $user->departments()->sync($request->input('departments', []));
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully');
    }

    public function index(UsersDataTable $dataTable)
    {
        return $dataTable->render('admin.users.index');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $departments = Department::all();
        $hospitals = Hospital::all();
        return view('admin.users.edit', compact('user', 'roles','departments','hospitals'));
    }

public function update(Request $request, User $user)
{
    $request->validate([
        'role_id' => 'required|exists:roles,id',
        'departments' => 'nullable|array',
        'departments.*' => 'exists:departments,id',
        'can_reopen_tickets' => 'nullable|boolean',
        'can_access_finished_tickets' => 'nullable|boolean',
        'hospitals' => 'nullable|array',
        'hospitals.*' => 'exists:hospitals,id',
    ]);

    if ($user->is_root) {
        $newDeptIds = []; // الروت لا يحتاج لربط أقسام محددة
    } else {
        $newDeptIds = $request->input('departments', []);
    }

    $currentDeptIds = $user->departments()->pluck('departments.id')->toArray();
    sort($currentDeptIds);
    sort($newDeptIds);

    $currentHospitalIds = $user->hospitals()->pluck('hospitals.id')->toArray();
    $newHospitalIds = $request->input('hospitals', []);
    sort($currentHospitalIds);
    sort($newHospitalIds);

    $newCanReopenValue = $request->has('can_reopen_tickets') ? 1 : 0;
    $newCanAccessFinishedTicketsValue = $request->has('can_access_finished_tickets') ? 1 : 0;

    $hasRoleChanged = $user->role_id != $request->role_id;
    $hasDeptsChanged = !$user->is_root && ($currentDeptIds != $newDeptIds);
    $hasHospitalsChanged = $currentHospitalIds != $newHospitalIds;
    $hasPermissionChanged = auth()->user()->is_root && (
        $user->can_reopen_tickets != $newCanReopenValue ||
        $user->can_access_finished_tickets != $newCanAccessFinishedTicketsValue
    );

    if (!$hasRoleChanged && !$hasDeptsChanged && !$hasHospitalsChanged && !$hasPermissionChanged) {
        return back()->with('info', 'هذه هي المعلومات المسجلة حالياً للمستخدم');
    }

    $user->update(['role_id' => $request->role_id]);

    $role = Role::find($request->role_id);

    // إدارة صلاحية إعادة فتح التذاكر
    if (auth()->user()->is_root) {
        if (strtolower($role->name) === 'admin') {
            $user->can_reopen_tickets = $newCanReopenValue;
            $user->can_access_finished_tickets = $newCanAccessFinishedTicketsValue;
        } else {
            $user->can_reopen_tickets = 0;
            $user->can_access_finished_tickets = 0;
        }
        $user->save();
    }

    // 2. تحديث الأقسام بناءً على الرتبة ونوع المستخدم
    if ($user->is_root) {
        // الروت لا يربط بأقسام محددة (صلاحية شاملة)
        $user->departments()->detach();
    } elseif (strtolower($role->name) === 'admin' || strtolower($role->name) === 'viewer') {
        $user->departments()->sync($request->input('departments', []));
    } else {
        $user->departments()->detach();
    }

    $user->hospitals()->sync($request->input('hospitals', []));

    return redirect()->route('admin.users.index')->with('success', 'تم تحديث البيانات بنجاح');
}





}
