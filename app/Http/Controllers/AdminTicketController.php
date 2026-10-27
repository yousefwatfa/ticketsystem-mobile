<?php

namespace App\Http\Controllers;

use App\DataTables\AdminTicketsDataTable;
use Illuminate\Http\Request;
use App\Services\IpTaggingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Ticket;
use App\Models\Category;
use App\Models\TicketLog;
use App\Models\User;
use App\Notifications\RealTimeNotification;
use App\Notifications\Telegram\NewMessageNotification;
use App\Notifications\Telegram\TicketAssignedNotification;
use App\Notifications\Telegram\TicketStatusNotification;
use App\Http\Traits\FilterAdminsByHospital;
use App\Services\TicketService;
use Auth;

class AdminTicketController extends Controller
{
    use AuthorizesRequests, FilterAdminsByHospital;


    public function index(AdminTicketsDataTable $dataTable)
{
    $user = Auth::user();

    $baseQuery = Ticket::query();

    if (!$user->is_root) {
        $baseQuery->whereHas('category.department', function ($query) use ($user) {
            $query->whereIn('departments.id', $user->departments->pluck('id'));
        });
    }

    $counts = [
        'allTickets'        => (clone $baseQuery)->count(),
        'openTickets'       => (clone $baseQuery)->where('status', 'open')->count(),
        'inProgressTickets' => (clone $baseQuery)->where('status', 'in_progress')->count(),
        'pendingTickets'    => (clone $baseQuery)->where('status', 'pending')->count(),
        'finishedTickets'   => (clone $baseQuery)->where('status', 'finished')->count(),
        'closedTickets'     => (clone $baseQuery)->where('status', 'closed')->count(),
    ];

    $departments = $user->is_root ? \App\Models\Department::all() : $user->departments;

    return $dataTable->render('admin.tickets.index', compact('departments', 'counts'));
}

    public function show(Ticket $ticket)
    {
        $this->authorize('update', $ticket);
        $user = Auth::user();
        $sessionKey = 'ticket_opened_' . $ticket->id;
        if (!session()->has($sessionKey)) {

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'content'   => "قام {$user->fullname} بفتح التذكرة",
        ]);

        $departmentAdmins = $this->getUsersAllowedToView($ticket);

        foreach ($departmentAdmins as $admin) {
            if ($admin->id !== $user->id) {
                if ($admin->telegram_number) {
                    $this->safeNotify($admin, new NewMessageNotification($ticket, $user, "{$user->fullname} قام بفتح التذكرة #{$ticket->id}"));
                }
                $this->safeNotify($admin, new RealTimeNotification(
                    " {$user->fullname}  قام بفتح التذكرة #{$ticket->id}",
                    $ticket->id,
                    $admin->id
                ));
            }
        }
        // send same notification to any department telegram chats
        $this->notifyDepartmentChats($ticket, " {$user->fullname}  قام بفتح التذكرة #{$ticket->id}");

        session()->put($sessionKey, true);
    }

        $ticket->load(['firstAdminReply.user', 'assignedUsers']);


        $users = User::whereHas('role', function ($query) {
            $query->where('name', 'Admin');
        })->latest()->get();

        $categories = Category::all();
        $ticketTag = IpTaggingService::getTagByIp($ticket->ip_address);



        return view('admin.tickets.show', compact('ticket', 'users', 'ticketTag', 'categories'));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);
        $currentUser = Auth::user();


        $departmentAdmins = $this->getUsersAllowedToView($ticket);

        if ($request->has('status') && $ticket->status != $request->status) {
            $oldStatus = $ticket->status_label;
            $ticket->update(['status' => $request->status]);
            $newStatus = $ticket->status_label;
            if($ticket->created_by !== $currentUser->id){
                    $this->safeNotify($ticket->creator, new RealTimeNotification(
                            "تم تغيير حالة التذكرة #{$ticket->id} من {$oldStatus} إلى {$newStatus}",
                                $ticket->id,
                                $ticket->created_by
                    ));
                }
            if ($ticket->creator->telegram_number) {
                $this->safeNotify($ticket->creator, new TicketStatusNotification($ticket, $oldStatus, $newStatus));
            }
            foreach ($departmentAdmins as $admin) {
                if ($admin->id !== $currentUser->id) {
                    $this->safeNotify($admin, new RealTimeNotification(
                    "تم تغيير حالة التذكرة #{$ticket->id} من {$oldStatus} إلى {$newStatus} بواسطة {$currentUser->fullname}",
                        $ticket->id,
                        $admin->id
                    ));
                    if ($admin->telegram_number) {
                        $this->safeNotify($admin, new TicketStatusNotification($ticket, $oldStatus, $newStatus));
                    }
                }
            }
            $this->notifyDepartmentChats($ticket, "تم تغيير حالة التذكرة #{$ticket->id} من {$oldStatus} إلى {$newStatus} بواسطة {$currentUser->fullname}");

            $activeStatuses = ['in_progress', 'pending', 'finished'];

            if (in_array($request->status, $activeStatuses) && $ticket->assignedUsers->isEmpty()) {
                $ticket->assignedUsers()->syncWithoutDetaching([$currentUser->id]);

                TicketLog::create([
                    'ticket_id' => $ticket->id,
                    'user_id'   => $currentUser->id,
                    'content'   => "تم تعيين التذكرة تلقائياً إلى {$currentUser->fullname} عند تغيير الحالة",
                ]);

                foreach ($departmentAdmins as $admin) {
                    if ($admin->id !== $currentUser->id) {
                        $this->safeNotify($admin, new RealTimeNotification(
                            "تم تعيين التذكرة #{$ticket->id} تلقائياً للموظف: {$currentUser->fullname}",
                            $ticket->id,
                            $admin->id
                        ));
                    }
                }
                $this->notifyDepartmentChats($ticket, "تم تعيين التذكرة #{$ticket->id} تلقائياً للموظف: {$currentUser->fullname}");

                $ticket->load('assignedUsers');
            }

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id'   => $currentUser->id,
                'content'   => "تم تغيير الحالة من {$oldStatus} إلى {$newStatus}",
            ]);
        }

        if ($request->has('assigned_users')) {
            $changes = $ticket->assignedUsers()->sync($request->assigned_users);

            if (!empty($changes['attached'])) {
                foreach ($changes['attached'] as $userId) {
                    $assignedUser = User::find($userId);
                    if ($assignedUser) {
                        $this->safeNotify($assignedUser, new RealTimeNotification(
                            "تم تكليفك بالعمل على التذكرة رقم #{$ticket->id}",
                            $ticket->id,
                            $assignedUser->id
                        ));
                        if ($assignedUser->telegram_number) {
                            $this->safeNotify($assignedUser, new TicketAssignedNotification($ticket, $assignedUser));
                        }

                        foreach ($departmentAdmins as $admin) {
                            if ($admin->id !== $assignedUser->id && $admin->id !== $currentUser->id) {
                                $this->safeNotify($admin, new RealTimeNotification(
                                    "تم تعيين الموظف {$assignedUser->fullname} للعمل على التذكرة #{$ticket->id} بواسطة {$currentUser->fullname}",
                                    $ticket->id,
                                    $admin->id
                                ));
                                if ($admin->telegram_number) {
                                    $this->safeNotify($admin, new TicketAssignedNotification($ticket, $assignedUser));
                                }
                            }
                        }
                        $this->notifyDepartmentChats($ticket, "تم تعيين الموظف {$assignedUser->fullname} للعمل على التذكرة #{$ticket->id} بواسطة {$currentUser->fullname}");
                        TicketLog::create([
                            'ticket_id' => $ticket->id,
                            'user_id'   => $currentUser->id,
                            'content'   => "تم تعيين التذكرة إلى المستخدم {$assignedUser->fullname}",
                        ]);
                    }
                }
            }

            if (!empty($changes['detached'])) {
                foreach ($changes['detached'] as $userId) {
                    $detachedUser = User::find($userId);
                    if ($detachedUser) {
                        TicketLog::create([
                            'ticket_id' => $ticket->id,
                            'user_id'   => $currentUser->id,
                            'content'   => "تم إلغاء تعيين المستخدم {$detachedUser->fullname} عن التذكرة",
                        ]);

                        if ($detachedUser->id !== $currentUser->id) {
                            $this->safeNotify($detachedUser, new RealTimeNotification(
                                "تمت إزالتك من فريق العمل على التذكرة رقم #{$ticket->id} بواسطة {$currentUser->fullname}",
                                $ticket->id,
                                $detachedUser->id
                            ));
                        }
                        foreach ($departmentAdmins as $admin) {
                            if ($admin->id !== $detachedUser->id && $admin->id !== $currentUser->id) {
                                $this->safeNotify($admin, new RealTimeNotification(
                                    "تم إلغاء تكليف  {$detachedUser->fullname} من التذكرة #{$ticket->id} بواسطة {$currentUser->fullname}",
                                    $ticket->id,
                                    $admin->id
                                ));
                            }
                        }
                        $this->notifyDepartmentChats($ticket, "تم إلغاء تكليف  {$detachedUser->fullname} من التذكرة #{$ticket->id} بواسطة {$currentUser->fullname}");
                    }
                }
            }
        }

        return back()->with('success', 'Ticket updated successfully');
    }

    public function reassignTicketCategory(Request $request, Ticket $ticket)
    {
        $senderName = auth()->user()->fullname;
        $admin = Auth::user();
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'reassign_reason' => 'required|string|max:500'
        ]);

        $newCategory = Category::findOrFail($request->category_id);
        $ticket->update(['category_id' => $request->category_id]);

        $ticket->ticketLogs()->create([
            'user_id' => $admin->id,
            'content' => "تم تغيير نوع مشكلة التذكرة إلى: {$newCategory->name}. السبب: {$request->reassign_reason}",
        ]);
        if($admin->id !== $ticket->created_by) {

            $this->safeNotify($ticket->creator, new RealTimeNotification(
                "تم تغيير نوع مشكلة التذكرة رقم #{$ticket->id} إلى: {$newCategory->name}",
                $ticket->id,
                $ticket->created_by
            ));
        }


        // For reassign, we need to use new category's department but still check hospital
        $tempTicket = $ticket->replicate();
        $tempTicket->category_id = $newCategory->id;
        $tempTicket->category = $newCategory;
        $newDepartmentAdmins = $this->getUsersAllowedToView($tempTicket);

        foreach ($newDepartmentAdmins as $deptAdmin) {
         if ($admin->id !== $deptAdmin->id) {

            $this->safeNotify($deptAdmin, new RealTimeNotification(
                "تم نقل تذكرة جديدة لقسمكم برقم #{$ticket->id} بواسطة {$senderName}",
                  $ticket->id,
                $deptAdmin->id
            ));
         }
        }
        $this->notifyDepartmentChats($ticket, "تم نقل تذكرة جديدة لقسمكم برقم #{$ticket->id} بواسطة {$senderName}");
        

        return redirect()->route('admin.tickets.index')->with('success', 'تم إعادة توجيه التذكرة بنجاح.');
    }

    public function reopenTickets(Ticket $ticket)
    {
        $this->authorize('update', $ticket);
        $sender = Auth::user();
        $senderName = $sender->fullname;

        $ticket->update(['status' => 'in_progress']);

        $ticket->ticketLogs()->create([
            'content' => "تم إعادة فتح التذكرة بواسطة {$senderName}",
            'user_id' => $sender->id,
        ]);

        $message = "تم إعادة فتح التذكرة رقم #{$ticket->id}";

        if ($ticket->user_id !== $sender->id) {
            $this->safeNotify($ticket->creator, new RealTimeNotification(
            $message,
            $ticket->id,
            $ticket->created_by
            ));
        }

        $departmentAdmins = $this->getUsersAllowedToView($ticket);

        foreach ($departmentAdmins as $admin) {
            if ($admin->id !== $sender->id) {
                $this->safeNotify($admin, new RealTimeNotification(
                    "إعادة فتح التذكرة #{$ticket->id} بواسطة {$senderName}",
                    $ticket->id,
                    $admin->id
                ));
            }
        }
        $this->notifyDepartmentChats($ticket, "إعادة فتح التذكرة #{$ticket->id} بواسطة {$senderName}");

        return redirect()->route('tickets.show', $ticket->id)->with('success', 'تم إعادة فتح التذكرة بنجاح.');
    }

    public function printTicket(Request $request, $id)
    {
        $ticket = Ticket::with('messages')->findOrFail($id);
        $ticketLogs = $ticket->ticketLogs;
        $viewPath = $request->has('full') ? 'admin.tickets.print_full' : 'admin.tickets.print';
        return view($viewPath, compact('ticket', 'ticketLogs'));
    }

    public function getTicketDetails($id)
    {
        $ticket = Ticket::with(['category', 'creator'])->find($id);
        $user = Auth::user();

        if ($user->role->name === 'Admin' || $user->is_root) {
            return response()->json([
                'status' => true,
                'data' => $ticket,
                'status_label' => $ticket->status_label
            ]);
        }
    }

    public function markAsFinishedTicket(Ticket $ticket, TicketService $tickets)
    {
        $this->authorize('update', $ticket);

        $tickets->markFinished(Auth::user(), $ticket);

        return redirect()->route('tickets.show', $ticket->id)->with('success', 'تم إنهاء التذكرة وتعيينها لك بنجاح.');
    }

    /**
     * Send a plain RealTimeNotification message to all telegram chats
     * associated with the ticket's department.  Chats with a hospital_id
     * are only notified when the ticket creator belongs to that hospital.
     */
    private function notifyDepartmentChats(Ticket $ticket, string $message)
    {
        $ticket->category->department->notifyTelegramChats($ticket, $message);
    }


}
