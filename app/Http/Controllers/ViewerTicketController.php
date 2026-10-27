<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\DataTables\ViewerTicketsTable;

class ViewerTicketController extends Controller
{
    public function index(ViewerTicketsTable $dataTable) {
            $user = \Auth::user();

        $baseQuery = Ticket::all();
          $counts = [
        'allTickets'         => (clone $baseQuery)->count(),
        'openTickets'        => (clone $baseQuery)->where('status', 'open')->count(),
        'inProgressTickets'  => (clone $baseQuery)->where('status', 'in_progress')->count(),
        'pendingTickets'     => (clone $baseQuery)->where('status', 'pending')->count(),
        'finishedTickets'     => (clone $baseQuery)->where('status', 'finished')->count(),
        'closedTickets'      => (clone $baseQuery)->where('status', 'closed')->count(),
    ];
        $departments = auth()->user()->departments;
        return $dataTable->render('viewer.tickets.index',compact('departments','counts'));
    }
}
