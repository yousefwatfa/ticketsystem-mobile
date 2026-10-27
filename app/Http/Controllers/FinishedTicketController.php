<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Http\Traits\FilterAdminsByHospital;



class FinishedTicketController extends Controller
{
    use FilterAdminsByHospital;

    protected function authorizeFinishedTicketsAccess(): void
    {
        $user = Auth::user();

        abort_unless($user && ($user->is_root || $user->can_access_finished_tickets), 403);
    }

    public function create() {
        $this->authorizeFinishedTicketsAccess();

        $categories = Category::all();
        $users = User::all();
        return view('admin.finished_tickets.create', compact('categories','users'));
    }
      public function store(Request $request) {
          $this->authorizeFinishedTicketsAccess();

        $user= Auth::user();
        $user_id = $user->id;

        $data = $request->validate([
            'title' => 'required',
            'description' => 'required',
            'office_location' =>'required',
            'category_id' => 'required|exists:categories,id',
            'user_id'     => 'required|exists:users,id',

        ]);
        $ticket = \App\Models\Ticket::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'office_location' =>$data['office_location'],
            'category_id' => $data['category_id'],
            'on_behalf_of'     => $data['user_id'],
            'created_by' => \Illuminate\Support\Facades\Auth::id(),
            'status' => 'closed',
            'ip_address' => $request->ip()
        ]);

            $ticket->assignedUsers()->attach($user_id);
            $onBehalfOfUser = \App\Models\User::find($data['user_id']);
               \App\Models\TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id'   => $user_id,
                'content'   => 'تم إنشاء مهمة منتهية نيابة عن (' . \App\Models\User::find($data['user_id'])->fullname . ') بواسطة ' . $user->fullname,
            ]);

            $this->safeNotify($onBehalfOfUser, new \App\Notifications\RealTimeNotification(
                "تم تسجيل وإغلاق تذكرة بنجاح نيابة عنك برقم #{$ticket->id}",
                $ticket->id,
                $onBehalfOfUser->id
            ));
            $departmentAdmins = $this->getUsersAllowedToView($ticket);

            foreach ($departmentAdmins as $admin) {
                if ($admin->id !== $user->id) {
                    $this->safeNotify($admin, new \App\Notifications\RealTimeNotification(
                        "تم إضافة مهمة منتهية جديدة في قسمكم برقم #{$ticket->id} بواسطة {$user->fullname}",
                        $ticket->id,
                        $admin->id
                    ));
                }
            }
            // also forward to department telegram chats
            try {
                $ticket->category->department->notifyTelegramChats($ticket, "تم إضافة مهمة منتهية جديدة في قسمكم برقم #{$ticket->id} بواسطة {$user->fullname}");
            } catch (\Exception $e) {
                logger()->warning('Department chat notify failed', ['error' => $e->getMessage()]);
            }

    return redirect()->route('admin.tickets.index', ['status' => 'closed'])
                        ->with('success', 'تم انشاء مهمة مكتملة بنجاح!');
    }

public function searchUsers(Request $request) {
    $this->authorizeFinishedTicketsAccess();

    try {
        $q = $request->input('q');

        $users = \App\Models\User::where('fullname', 'LIKE', "%{$q}%")
                    ->orWhere('username', 'LIKE', "%{$q}%")
                    ->limit(10)
                    ->get();

        $data = $users->map(function($user) {
            return [
                'id'   => $user->id,
                'text' => $user->fullname . " (" . $user->username . ")"
            ];
        });

        return response()->json($data);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
}

