<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use App\Notifications\RealTimeNotification;
use App\Http\Traits\FilterAdminsByHospital;
use Illuminate\Support\Str;
use App\Services\TicketService;
use Illuminate\Auth\Access\AuthorizationException;
class MessageController extends Controller
{
    use FilterAdminsByHospital;

  public function store(Request $request, TicketService $tickets) {
    $request->validate([
        'ticket_id' => 'required|exists:tickets,id',
        'content' => 'required'
    ]);

    $ticket = Ticket::findOrFail($request->ticket_id);

    try {
        $tickets->addMessage(Auth::user(), $ticket, $request->content);
    } catch (AuthorizationException $e) {
        abort(403);
    }

    return back()->with('success', 'تم إرسال الرد بنجاح');
}
}
