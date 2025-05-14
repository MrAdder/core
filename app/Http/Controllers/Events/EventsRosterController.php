<?php

namespace App\Http\Controllers\Events;

use App\Models\Events\EventsRoster;
use Illuminate\Http\Request;

class EventsRosterController extends \App\Http\Controllers\BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rosters = EventsRoster::all();
        return view('events.index', compact('rosters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('events.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'user_id' => 'required|exists:users,id',
        ]);

        EventsRoster::create($request->all());
        return redirect()->route('events.index')->with('success', 'Roster created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(EventsRoster $eventsRoster)
    {
        return view('events.show', compact('eventsRoster'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EventsRoster $eventsRoster)
    {
        return view('events.edit', compact('eventsRoster'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateRoster(Request $request, EventsRoster $eventsRoster)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $eventsRoster->update($request->all());
        return redirect()->route('events.index')->with('success', 'Roster updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EventsRoster $eventsRoster)
    {
        $eventsRoster->delete();
        return redirect()->route('events.index')->with('success', 'Roster deleted successfully.');
    }
}
