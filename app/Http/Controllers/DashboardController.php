<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
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
}

