<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Request;
use App\Models\Spot;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use App\Events\NotificationEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SpotController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show'])
        ];
    }
    public function index()
    {
        return response()->json(
            Spot::all()->map(function ($spot) {
                return [
                    'id' => (int)$spot->id,
                    'name' => $spot->name,
                    'latitude' => (float)$spot->latitude,
                    'longitude' => (float)$spot->longitude,
                    'description' => $spot->description,
                    'fish_species' => $spot->fish_species,
                    'recommended_techniques' => $spot->recommendedTechniques,
                    'depth' => $spot->depth !== null ? (float)$spot->depth : null,
                    'upvotes' => $spot->upvotes ?? 0, // Valeur par défaut
                    'downvotes' => $spot->downvotes ?? 0, // Valeur par défaut
                    'voter_ids' => $spot->voter_ids ?? []
                ];
            })
        );
    }


    public function store(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'description' => 'required|string|max:255',
            'fish_species' => 'required|string|max:255',
            'recommendedTechniques' => 'nullable|string|max:255', // ✅ string obligatoire
            'depth' => 'required|numeric', // ✅ numérique obligatoire
        ]);

        $spot = $request->user()->spots()->create($fields);
        // 🔔 Notification à tous les utilisateurs sauf l’auteur
    $sender = $request->user();
    $usersToNotify = User::where('id', '!=', $sender->id)->get();

    foreach ($usersToNotify as $user) {
        $notif = Notification::create([
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
            'message' => $sender->name . ' a ajouté un nouveau spot de pêche : ' . $spot->name,
            'type' => 'spot',
            'spot_id' => $spot->id, // Ajoute ce champ dans ta table `notifications` si nécessaire
        ]);

        broadcast(new NotificationEvent($notif))->toOthers(); // Pour ne pas envoyer au sender
    }
        return response()->json($spot, 201);
    }

    public function show($id)
    {
        $spot = Spot::findOrFail($id);

        return response()->json($spot);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'description' => 'required|string|max:255',
            'fish_species' => 'required|string|max:255',
            'recommendedTechniques' => 'required|string|max:255', // ✅ string obligatoire
            'depth' => 'required|numeric', // ✅ numérique obligatoire
        ]);

        $spot = Spot::where('user_id', Auth::id())->findOrFail($id);
        $spot->update($request->all());
        return response()->json($spot);
    }
    public function destroy($id)
    {
        $spot = Spot::where('user_id', Auth::id())->findOrFail($id);
        $spot->delete();

        return response()->json(null, 204);
    }

    // Ajoutez cette méthode dans SpotController.php
    public function vote(Request $request, $id)
    {
        try {
            $request->validate([
                'is_upvote' => 'required|boolean',
                'user_id' => 'required|integer|exists:users,id'
            ]);

            $spot = Spot::findOrFail($id);
            $voterIds = $spot->voter_ids ?? [];

            if (in_array($request->user_id, $voterIds)) {
                return response()->json([
                    'message' => 'Vous avez déjà voté pour ce spot',
                    'error' => 'already_voted'
                ], 409);
            }

            $spot->update([
                $request->is_upvote ? 'upvotes' : 'downvotes' => DB::raw(($request->is_upvote ? 'upvotes' : 'downvotes') . ' + 1'),
                'voter_ids' => array_merge($voterIds, [$request->user_id])
            ]);

            if ($spot->downvotes >= 5) {
                $spot->delete();
                return response()->json([
                    'message' => 'Spot supprimé suite à plusieurs signalements',
                    'deleted' => true
                ], 200);
            }

            return response()->json([
                'message' => 'Vote enregistré',
                'upvotes' => $spot->upvotes,
                'downvotes' => $spot->downvotes
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Spot non trouvé'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
