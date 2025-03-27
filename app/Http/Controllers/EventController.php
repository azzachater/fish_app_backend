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
        return[
            new Middleware('auth:sanctum',except:['index','show'])
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EventResource::collection(Event::with('user')->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    // Validation des données
    $validated = $request->validate([
        'name' => 'required|max:255',
        'description' => 'nullable|string',
        'start_time' => 'required|date',
        'end_time' => 'required|date|after:start_time', // Utiliser la syntaxe correcte
    ]);

    // Création de l'événement avec les données validées
    $event = Event::create([
        'name' => $validated['name'],
        'description' => $validated['description'],
        'start_time' => $validated['start_time'],
        'end_time' => $validated['end_time'],
        'user_id' => $request->user()->id,
    ]);

    // Retourner la réponse avec le nouvel événement
    return new EventResource($event);
}


    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    { 
        $event->load('user','participants');
        return  new EventResource($event::with('user')->get());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
{
    // Autoriser la modification uniquement pour le propriétaire de l'événement
    Gate::authorize('modify', $event);

    // Validation des données
    $validated = $request->validate([
        'name' => 'sometimes|max:255',
        'description' => 'nullable|string',
        'start_time' => 'required|date',
        'end_time' => 'required|date|after:start_time', // Utiliser la syntaxe correcte
    ]);

    // Mise à jour de l'événement avec les données validées
    $event->update([
        'name' => $validated['name'] ?? $event->name,
        'description' => $validated['description'] ?? $event->description,
        'start_time' => $validated['start_time'] ?? $event->start_time,
        'end_time' => $validated['end_time'] ?? $event->end_time,
    ]);

    // Retourner la réponse avec l'événement mis à jour
    return new EventResource($event);
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        Gate::authorize('modify', $event);
        $event->delete();

        return ['message' => 'the event was deleted'];
    }
}
