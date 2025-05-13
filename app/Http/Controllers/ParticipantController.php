<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\ParticipantResource;
use App\Models\Participant;
use App\Models\Event;
use App\Models\User;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;

class ParticipantController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return[
            new Middleware('auth:sanctum',except:['index','show'])
        ];
    }
    public function index()
{
    $events = Event::with(['participants.user'])->get();

    return response()->json([
        'data' => $events->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'location' => $event->location,
                'date' => $event->date,
                'user_id' => $event->user_id,
                'participants' => $event->participants->map(function ($participant) {
                    return [
                        'user_id' => $participant->user_id,
                        'user' => [
                            'id' => $participant->user->id,
                            'name' => $participant->user->name,
                            'avatar' => $participant->user->avatar
                            // Ajoutez d'autres champs si nécessaire
                        ]
                    ];
                })
            ];
        })
    ]);
}


//Store a newly created resource in storage.
public function store(Request $request, $eventId) // Ajoutez $eventId comme paramètre
{
    $request->validate([
        'user_id' => 'required|exists:users,id'
    ]);

    $event = Event::findOrFail($eventId); // Utilisez le $eventId du paramètre
    $user = User::findOrFail($request->user_id);

    if ($event->participants()->where('user_id', $user->id)->exists()) {
        return response()->json(['message' => 'Déjà inscrit'], 409);
    }

    $participant = $event->participants()->create([
        'user_id' => $user->id
    ]);

    return response()->json([
        'message' => 'Inscription réussie',
        'event' => $event->load(['participants.user']) // Chargez les relations
    ], 201);
}
    public function show(Event $event, Participant $participant)
{
    // Vérifier si le participant appartient bien à cet événement
    if ($participant->event_id !== $event->id) {
        return response()->json(['message' => 'Ce participant ne fait pas partie de cet événement.'], 404);
    }

    // Retourner les informations du participant
    return response()->json([
        'id' => $participant->id,
        'name' => $participant->user->name,
        'email' => $participant->user->email,
        'event' => $event->name,
        'registered_at' => $participant->created_at,
    ]);
}


    public function destroy(Event $event, Participant $participant)
    {
        // Vérifier si l'utilisateur a le droit de supprimer ce participant
        Gate::authorize('modify', $participant);

        // Supprimer le participant
        $participant->delete();

        return response()->json(['message' => 'Le participant a été supprimé avec succès.']);
    }
}