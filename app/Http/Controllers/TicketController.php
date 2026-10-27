<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\Attachment;

use App\Models\TicketLog;
use App\Models\Role;
use App\Models\User;

use App\Notifications\RealTimeNotification;
use App\Http\Traits\FilterAdminsByHospital;
use App\Services\TicketService;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    use FilterAdminsByHospital;
   public function index(\App\DataTables\TicketsDataTable $dataTable) {
    $user = \Auth::user();

    $baseQuery = Ticket::where('created_by', $user->id);

    $counts = [
        'allTickets'         => (clone $baseQuery)->count(),
        'openTickets'        => (clone $baseQuery)->where('status', 'open')->count(),
        'inProgressTickets'  => (clone $baseQuery)->where('status', 'in_progress')->count(),
        'pendingTickets'     => (clone $baseQuery)->where('status', 'pending')->count(),
        'finishedTickets'     => (clone $baseQuery)->where('status', 'finished')->count(),
        'closedTickets'      => (clone $baseQuery)->where('status', 'closed')->count(),
    ];
     $recentTickets = $baseQuery
            ->with('category', 'assignedUsers', 'creator')
            ->latest()
            ->get();


    return $dataTable->render('dashboard', compact('counts','recentTickets'));
}

    public function create() {
        $categories = \App\Models\Category::all();
        return view('tickets.create', compact('categories'));
    }

    public function store(Request $request, TicketService $tickets) {
        $user = Auth::user();

        if ($tickets->hasTicketAwaitingConfirmation($user)) {
            return redirect()->back()->with('error', 'لديك تذكرة منتهية بانتظار تأكيدك، يرجى إغلاقها أولاً قبل إنشاء تذكرة جديدة.');
        }

        $messages = [
            'title.required' => 'حقل العنوان مطلوب.',
            'description.required' => 'يرجى كتابة وصف للمشكلة.',
            'office_location.required' => 'يرجى تحديد مكان المشكلة.',
            'category_id.required' => 'يجب اختيار نوع المشكلة للتذكرة.',
            'client_hostname.max' => 'اسم الجهاز طويل جداً.',
            'client_ip.max' => 'صيغة عنوان IP غير صحيحة.',
            'client_ips.json' => 'قائمة عناوين IP غير صالحة.',
            'attachments.*.mimes' => 'عفواً، المرفقات يجب أن تكون ملفات من نوع: (jpg, png, pdf, docx, xlsx) فقط.',
            'attachments.*.max' => 'حجم الملف يجب أن لا يتجاوز 2 ميجابايت.',
        ];
        
     
        $data = $request->validate([
            'title' => 'required',
            'description' => 'required',
            'office_location' => 'required',
            'category_id' => 'required|exists:categories,id',
            'client_hostname' => 'nullable|string|max:255',
            'client_ip' => 'nullable|string|max:45',
            'client_ips' => 'nullable|json',
            'attachments.*' => 'nullable|file|mimes:jpg,png,pdf,docx,xlsx,xls|max:2048',
        ], $messages);

        $clientIps = [];
        if (!empty($data['client_ips'])) {
            $decodedIps = json_decode($data['client_ips'], true);
            if (is_array($decodedIps)) {
                $clientIps = array_values(array_filter($decodedIps, fn($ip) => is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP)));
            }
        }

        $clientIp = $data['client_ip'] ?? null;
        if (!is_string($clientIp) || !filter_var($clientIp, FILTER_VALIDATE_IP)) {
            $clientIp = null;
        }

        if (!$clientIp && !empty($clientIps)) {
            $clientIp = $clientIps[0];
        }

        $resolvedIp = $clientIp ?: $request->ip();

        $ticket = $tickets->create(
            $user,
            $data,
            $request->file('attachments', []),
            $resolvedIp,
            $data['client_hostname'] ?? null,
            $clientIps
        );

        return redirect()->route('tickets.show', $ticket)->with('success', 'تم إنشاء التذكرة بنجاح!');
        }

    public function show(\App\Models\Ticket $ticket) {

        if (\Auth::user()->cannot('view', $ticket)) {
            abort(403);
        }

       $ticket->load([
        'messages' => function($query) {
            $query->latest();
        },
        'messages.user',
        'attachments',
        'creator',
        'category.department.mainDepartment'
    ]);


        return view('tickets.show', compact('ticket'));
    }

       public function finishTicket(Ticket $ticket, TicketService $tickets) {
        if (Auth::id() !== $ticket->created_by) {
            abort(403);
        }

        $tickets->confirmClose(Auth::user(), $ticket);

        return redirect()->back()->with('success', 'تم إغلاق التذكرة بنجاح.');
    }



public function filesDestroy(Attachment $attachment) {
    $ticket = $attachment->attachable;
    $user = auth()->user();

    if (!$ticket) {
        return back()->with('error', 'التذكرة غير موجودة');
    }

    if ($attachment->file_path && file_exists(public_path($attachment->file_path))) {
        \File::delete(public_path($attachment->file_path));
    }

    \App\Models\TicketLog::create([
        'ticket_id' => $ticket->id,
        'user_id'   => $user->id,
        'content'   => 'تم حذف المرفق: ' . $attachment->file_name . ' بواسطة ' . $user->fullname,
    ]);

    if ($user->id === $ticket->created_by) {
        $departmentAdmins = \App\Models\User::whereHas('departments', function ($query) use ($ticket) {
                $query->where('departments.id', $ticket->category->department_id);
            })
            ->whereHas('role', function ($query) {
                $query->where('name', 'Admin');
            })
            ->get();

        foreach ($departmentAdmins as $admin) {
            $this->safeNotify($admin, new RealTimeNotification(
                "قام المستخدم {$user->fullname} بحذف مرفق من التذكرة رقم #{$ticket->id}",
                $ticket->id,
                $admin->id
            ));
        }
    }

    $attachment->delete();

    if (request()->ajax()) {
        return response()->json(['success' => true, 'message' => 'تم الحذف بنجاح']);
    }

    return back()->with('success', 'تم حذف المرفق بنجاح');
}


public function addAttachment(Request $request)
{
        $messages = [
            'attachment.required' => 'يرجى اختيار ملف أولاً.',
            'attachment.file'     => 'يجب أن يكون المرفق ملفاً صحيحاً.',
            'attachment.mimes'    => 'عفواً، المرفق يجب أن يكون من نوع: (jpg, png, pdf, docx, xlsx, xls) فقط.',
            'attachment.max'      => 'حجم الملف يجب أن لا يتجاوز 2 ميجابايت.',
            'ticket_id.required'  => 'رقم التذكرة مفقود.',
            'ticket_id.exists'    => 'التذكرة المحددة غير موجودة.',
        ];

        $request->validate([
            'attachment' => 'required|file|mimes:jpg,png,pdf,docx,xlsx,xls|max:2048',
            'ticket_id'  => 'required|exists:tickets,id'
        ], $messages);

    $ticket = Ticket::findOrFail($request->ticket_id);
    $user = auth()->user();

    if ($request->hasFile('attachment')) {
        $file = $request->file('attachment');
        $path = $file->store('tickets/attachments', 'public');

        $attachment = $ticket->attachments()->create([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientOriginalExtension(),
        ]);

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'content'   => 'قام ' . $user->fullname . ' بإضافة مرفق جديد: ' . $attachment->file_name,
        ]);

        if ($user->id === $ticket->created_by) {
            $departmentAdmins = $this->getUsersAllowedToView($ticket);

            foreach ($departmentAdmins as $admin) {
                $this->safeNotify($admin, new RealTimeNotification(
                    "مرفق جديد من " . $user->fullname . " على التذكرة #" . $ticket->id,
                    $ticket->id,
                    $admin->id
                ));
            }
            try {
            $ticket->category->department->notifyTelegramChats($ticket, "مرفق جديد من " . $user->fullname . " على التذكرة #" . $ticket->id);
        } catch (\Exception $e) {
            logger()->warning('Department chat notify failed', ['error' => $e->getMessage()]);
        }
        }

        return response()->json([
            'id'        => $attachment->id,
            'file_name' => $attachment->file_name,
            'file_type' => $attachment->file_type,
            'url'       => asset('storage/' . $attachment->file_path),
            'success'   => true
        ]);
    }

    return response()->json(['success' => false, 'message' => 'لم يتم العثور على ملف'], 400);
}
}
