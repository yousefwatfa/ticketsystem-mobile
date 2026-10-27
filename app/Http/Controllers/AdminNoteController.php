<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Note;
use App\Models\Ticket;


class AdminNoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
       public function store(Request $request, Ticket $ticket)
    {
        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $ticketNotes = $ticket->internalNotes()->create([
            'user_id' =>  \Illuminate\Support\Facades\Auth::id(),
            'body' => $request->body,
        ]);

        return back()->with('success', 'تم إضافة الملاحظة الداخلية بنجاح.');

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Note $note)
    {
        dd($note);
    }

    /**
     * Update the specified resource in storage.
     */
  public function update(Request $request, Ticket $ticket, Note $note) {
        $request->validate(['body' => 'required|string|max:1000']);

        $note->update(['body' => $request->body]);

        return back()->with('success', 'تم تحديث الملاحظة');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticket $ticket, Note $note)
    {
        $note->delete();
        return back()->with('success', 'تم حذف الملاحظة بنجاح.');

    }
}
