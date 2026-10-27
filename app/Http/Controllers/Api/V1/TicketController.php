<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AttachmentResource;
use App\Http\Resources\Api\MessageResource;
use App\Http\Resources\Api\TicketResource;
use App\Models\Category;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

/**
 * The signed-in user's own tickets (staff side of the app).
 */
class TicketController extends Controller
{
    private const STATUSES = ['open', 'in_progress', 'pending', 'finished', 'closed'];

    public function categories()
    {
        return response()->json([
            'data' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function index(Request $request)
    {
        $request->validate(['status' => 'nullable|in:all,' . implode(',', self::STATUSES)]);
        $user = $request->user();
        $base = Ticket::where('created_by', $user->id);

        $tickets = (clone $base)
            ->when($request->filled('status') && $request->status !== 'all', fn ($q) => $q->where('status', $request->status))
            ->with(['category', 'assignedUsers'])
            ->withCount('messages')
            ->latest()
            ->paginate(20);

        $counts = (clone $base)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return TicketResource::collection($tickets)->additional([
            'counts' => ['all' => (int) $counts->sum()] + collect(self::STATUSES)->mapWithKeys(fn ($s) => [$s => (int) ($counts[$s] ?? 0)])->all(),
        ]);
    }

    public function store(Request $request, TicketService $tickets)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'office_location' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,docx,xlsx,xls|max:2048',
        ], [
            'attachments.*.max' => 'حجم الملف يجب أن لا يتجاوز 2 ميجابايت.',
            'attachments.*.mimes' => 'المرفقات يجب أن تكون من نوع: jpg, png, pdf, docx, xlsx.',
        ]);

        try {
            $ticket = $tickets->create($request->user(), $data, $request->file('attachments', []), $request->ip());
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'finished_ticket_pending'], 409);
        }

        return (new TicketResource($this->loadDetails($ticket)))->response()->setStatusCode(201);
    }

    public function show(Request $request, Ticket $ticket)
    {
        $this->authorizeView($request, $ticket);

        return new TicketResource($this->loadDetails($ticket));
    }

    public function reply(Request $request, Ticket $ticket, TicketService $tickets)
    {
        $this->authorizeView($request, $ticket);
        $data = $request->validate(['content' => 'required|string|max:5000']);

        if ($ticket->status === 'closed') {
            return response()->json(['message' => 'التذكرة مغلقة ولا يمكن الرد عليها.'], 422);
        }

        try {
            $message = $tickets->addMessage($request->user(), $ticket, $data['content']);
        } catch (AuthorizationException $e) {
            abort(403);
        }

        return (new MessageResource($message->load('user.role')))->response()->setStatusCode(201);
    }

    public function attach(Request $request, Ticket $ticket, TicketService $tickets)
    {
        $this->authorizeView($request, $ticket);
        $request->validate(['file' => 'required|file|mimes:jpg,jpeg,png,pdf,docx,xlsx,xls|max:2048']);

        $user = $request->user();
        if ($ticket->status === 'closed' || ($ticket->created_by !== $user->id && $user->role?->name !== 'Admin' && !$user->is_root)) {
            abort(403);
        }

        $attachment = $tickets->storeAttachment($ticket, $request->file('file'));
        \App\Models\TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'content' => 'قام ' . $user->fullname . ' بإضافة مرفق جديد: ' . $attachment->file_name,
        ]);

        return (new AttachmentResource($attachment))->response()->setStatusCode(201);
    }

    public function confirm(Request $request, Ticket $ticket, TicketService $tickets)
    {
        if ($ticket->status !== 'finished') {
            return response()->json(['message' => 'التذكرة ليست بانتظار التأكيد.'], 422);
        }

        try {
            $tickets->confirmClose($request->user(), $ticket);
        } catch (AuthorizationException $e) {
            abort(403);
        }

        return new TicketResource($this->loadDetails($ticket->fresh()));
    }

    public function notFixed(Request $request, Ticket $ticket, TicketService $tickets)
    {
        try {
            $tickets->reportNotFixed($request->user(), $ticket);
        } catch (AuthorizationException $e) {
            abort(403);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return new TicketResource($this->loadDetails($ticket->fresh()));
    }

    private function authorizeView(Request $request, Ticket $ticket): void
    {
        if ($request->user()->cannot('view', $ticket)) {
            abort(403);
        }
    }

    private function loadDetails(Ticket $ticket): Ticket
    {
        return $ticket->load([
            'category',
            'creator',
            'assignedUsers',
            'attachments',
            'messages' => fn ($q) => $q->oldest(),
            'messages.user.role',
        ]);
    }
}
