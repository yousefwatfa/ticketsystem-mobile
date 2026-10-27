<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Department tickets for admins. The list mirrors TicketPolicy::update
 * (ticket in one of the admin's departments or assigned to them, plus the
 * hospital check; root sees everything), so every ticket listed can be acted on.
 */
class AdminTicketController extends Controller
{
    private const STATUSES = ['open', 'in_progress', 'pending', 'finished', 'closed'];

    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:all,' . implode(',', self::STATUSES),
            'search' => 'nullable|string|max:100',
            'mine' => 'nullable|boolean',
        ]);

        $base = $this->scopedQuery($request);

        $tickets = (clone $base)
            ->when($request->filled('status') && $request->status !== 'all', fn ($q) => $q->where('status', $request->status))
            ->when($request->boolean('mine'), fn ($q) => $q->whereHas('assignedUsers', fn ($a) => $a->where('users.id', $request->user()->id)))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim($request->search);
                $q->where(function ($w) use ($term) {
                    $w->where('title', 'like', "%{$term}%")
                        ->orWhere('office_location', 'like', "%{$term}%")
                        ->orWhereHas('creator', fn ($c) => $c->where('fullname', 'like', "%{$term}%"));
                    if (ctype_digit($term)) {
                        $w->orWhere('id', (int) $term);
                    }
                });
            })
            ->with(['category', 'creator', 'assignedUsers'])
            ->withCount('messages')
            ->latest()
            ->paginate(20);

        $counts = (clone $base)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return TicketResource::collection($tickets)->additional([
            'counts' => ['all' => (int) $counts->sum()] + collect(self::STATUSES)->mapWithKeys(fn ($s) => [$s => (int) ($counts[$s] ?? 0)])->all(),
        ]);
    }

    public function take(Request $request, Ticket $ticket, TicketService $tickets)
    {
        if (in_array($ticket->status, ['finished', 'closed'])) {
            return response()->json(['message' => 'التذكرة منتهية بالفعل.'], 422);
        }

        try {
            $tickets->take($request->user(), $ticket);
        } catch (AuthorizationException $e) {
            abort(403);
        }

        return new TicketResource($ticket->fresh()->load(['category', 'creator', 'assignedUsers']));
    }

    public function finish(Request $request, Ticket $ticket, TicketService $tickets)
    {
        if (in_array($ticket->status, ['finished', 'closed'])) {
            return response()->json(['message' => 'التذكرة منتهية بالفعل.'], 422);
        }

        try {
            $tickets->markFinished($request->user(), $ticket);
        } catch (AuthorizationException $e) {
            abort(403);
        }

        return new TicketResource($ticket->fresh()->load(['category', 'creator', 'assignedUsers']));
    }

    private function scopedQuery(Request $request): Builder
    {
        $user = $request->user();
        $query = Ticket::query();

        if ($user->is_root) {
            return $query;
        }

        $departmentIds = $user->departments()->pluck('departments.id');
        $query->where(function ($q) use ($departmentIds, $user) {
            $q->whereHas('category', fn ($c) => $c->whereIn('department_id', $departmentIds))
                ->orWhereHas('assignedUsers', fn ($a) => $a->where('users.id', $user->id));
        });

        $hospitalIds = $user->hospitals()->pluck('hospitals.id');
        if ($hospitalIds->isNotEmpty()) {
            $query->whereHas('creator.hospitals', fn ($h) => $h->whereIn('hospitals.id', $hospitalIds));
        }

        return $query;
    }
}
