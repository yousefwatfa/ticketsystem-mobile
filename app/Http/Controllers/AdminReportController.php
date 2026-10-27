<?php

namespace App\Http\Controllers;

use App\DataTables\AdminReportsDataTable;
use App\Models\Department;
use App\Models\MainDepartment;
use App\Models\Ticket;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function index(AdminReportsDataTable $dataTable)
    {
        $user = auth()->user();

        $departments = $user->is_root
            ? Department::orderBy('name')->get()
            : Department::whereIn('main_department_id', $user->departments()->pluck('main_department_id'))
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->when(!$user->is_root, function ($query) use ($user) {
                $query->whereHas('departments', function ($q) use ($user) {
                    $q->whereIn('departments.main_department_id', $user->departments()->pluck('main_department_id'));
                });
            })
            ->orderBy('fullname')
            ->get(['id', 'fullname', 'username']);

        $mainDepartments = $user->is_root
            ? MainDepartment::orderBy('name')->get()
            : MainDepartment::whereIn('id', $user->departments()->pluck('main_department_id'))
            ->orderBy('name')
            ->get();

        return $dataTable->render('admin.reports.index', compact('departments', 'users', 'mainDepartments'));
    }

    public function generatePdf(Request $request)
    {
        $currentUser = auth()->user();
        $query = Ticket::query()->whereIn('status', ['closed', 'finished']);

        if (!$currentUser->is_root) {
            $userMainDepts = $currentUser->departments()->pluck('main_department_id');
            $query->whereHas('category.department', function ($q) use ($userMainDepts) {
                $q->whereIn('main_department_id', $userMainDepts);
            });
        }


        if ($request->filled('main_department_id') && $request->main_department_id !== 'all') {
            $query->whereHas('category.department', function ($q) use ($request) {
                $q->where('main_department_id', $request->main_department_id);
            });
        }

        if ($request->filled('department_id') && $request->department_id !== 'all') {
            $query->whereHas('category.department', function ($q) use ($request) {
                $q->where('id', $request->department_id);
            });
        }

        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $selectedUserId = $request->user_id;
            $query->whereHas('assignedUsers', function ($q) use ($selectedUserId) {
                $q->where('users.id', $selectedUserId);
            });
        }

        if ($request->filled('title')) {
            $searchTerm = trim($request->title);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                    ->orWhere('id', (int)str_replace('#', '', $searchTerm));
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->category . '%');
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $tickets = $query->with(['category.department', 'creator', 'assignedUsers'])->latest()->get();

        $selectedUser = ($request->filled('user_id') && $request->user_id !== 'all')
            ? User::find($request->user_id)
            : null;

        $pdf = Pdf::loadView('admin.reports.pdf_template', [
            'tickets' => $tickets,
            'reportUser' => $selectedUser,
            'filters' => $request->all(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('report-' . now()->format('Y-m-d') . '.pdf');
    }

    protected function resolveReportUser(Request $request, User $currentUser): ?User
    {
        if ($currentUser->is_root) {
            if (!$request->filled('user_id')) {
                return null;
            }

            return User::findOrFail($request->integer('user_id'));
        }

        if ($request->filled('user_id')) {
            $selectedUser = User::findOrFail($request->integer('user_id'));
            if (!$selectedUser->departments()->whereIn('departments.main_department_id', $currentUser->departments()->pluck('main_department_id'))->exists()) {
                abort(403, 'غير مصرح لك بالوصول إلى تقارير هذا المستخدم.');
            }
            return $selectedUser;
        }

        return null;
    }
}
