<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\EventResource;

class EventController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show'])
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EventResource::collection(
            Event::with(['user', 'participants'])
                ->orderBy('date', 'desc')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'location' => 'required',
            'description' => 'nullable|string',
            'date' => 'required|date_format:Y-m-d', // Format correspondan
        ]);

        $event = Event::create([
            'title' => $validated['title'],
            'location' => $validated['location'],
            'description' => $validated['description'],
            'date' => $validated['date'], // Laravel convertit automatiquement en DateTime
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'id' => $event->id,
            'title' => $event->title,
            'location' => $event->location,
            'description' => $event->description,
            'date' => $event->date, // Laravel gère automatiquement la conversion en JSON
            'user_id' => $event->user_id,
            'participants' => []
        ], 201);
    }


    public function show(Event $event)
    {
        $event->load('user', 'participants');
        return new EventResource($event);
    }

    public function update(Request $request, Event $event)
    {
        Gate::authorize('modify', $event);

        $validated = $request->validate([
            'title' => 'required|max:255',
            'location' => 'required',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'participants' => 'required|array', // Validation des participants
        ]);

        $event->update([
            'title' => $validated['title'],
            'location' => $validated['location'],
            'description' => $validated['description'],
            'date' => $validated['date'],
            'participants' => $validated['participants'], // Mise à jour des participants
        ]);

        return response()->json([
            'id' => $event->id,
            'title' => $event->title,
            'location' => $event->location,
            'description' => $event->description,
            'date' => $event->date->format('Y-m-d'),
            'participants' => $event->participants ?? []
        ]);
    }

    public function destroy(Event $event)
    {
        //gate pour verifier que l user est actuellement
        Gate::authorize('modify', $event);
        $event->delete();

        return ['message' => 'the event was deleted'];
    }
    public function joinEvent(Event $event, Request $request)
    {
        $user = $request->user();
        $event->participants()->syncWithoutDetaching([$user->id]);

        return response()->json([
            'message' => 'Participation enregistrée',
            'event' => $event->load(['user', 'participants'])
        ], 201);
    }
    public function getParticipants(Event $event)
{
    $event->load('participants');

    return response()->json([
        'participants' => $event->participants
    ]);
}
}
