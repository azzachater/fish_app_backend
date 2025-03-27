<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\ParticipantResource;
use App\Models\Participant;
use App\Models\Event;
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
    public function index(Event $event)
    {
        // Charger les participants et leurs utilisateurs
        $participants = $event->participants()->with('user')->get();
    
        return response()->json([
            'event' => $event->name,
            'participants' => $participants->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'name' => $participant->user->name,
                    'email' => $participant->user->email,
                    'created_at' => $participant->created_at,
                ];
            }),
        ]);
    }
    

//Store a newly created resource in storage. 
public function store(Request $request)
    {
        // Vérifier si l'utilisateur est connecté (déjà géré par middleware)
        
        // Valider la requête
        $request->validate([
            'event_name' => 'required|string|exists:events,name',
        ]);

        // Récupérer l'utilisateur authentifié
        $user = \Illuminate\Support\Facades\Auth::user();

        // Récupérer l'événement par son nom
        $event = Event::where('name', $request->event_name)->firstOrFail();

        // Vérifier si l'utilisateur est déjà inscrit
        if ($event->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Vous êtes déjà inscrit à cet événement.'], 400);
        }

        // Ajouter le participant
        $participant = $event->participants()->create([
            'user_id' => $user->id,
        ]);

        return new ParticipantResource($participant);
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