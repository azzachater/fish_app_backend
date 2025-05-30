<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Request;
use App\Models\Spot;
use Illuminate\Support\Facades\Auth;
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
                    'recommendedTechniques' => $spot->recommendedTechniques,
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
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'description' => 'required|string|max:255',
            'fish_species' => 'required|string|max:255',
            'recommendedTechniques' => 'nullable|string|max:255',
            'depth' => 'nullable|numeric|min:0',
        ]);

        $spot = $request->user()->spots()->create($fields);
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
    $request->validate([
        'is_upvote' => 'required|boolean',
    ]);

    $spot = Spot::findOrFail($id);

    $userId = $request->user()->id;
    $voterIds = $spot->voter_ids ?? [];

    if (in_array($userId, $voterIds)) {
        return response()->json([
            'message' => 'Vous avez déjà voté pour ce spot',
            'error' => 'already_voted'
        ], 409);
    }

    $spot->update([
        $request->is_upvote ? 'upvotes' : 'downvotes' => DB::raw(($request->is_upvote ? 'upvotes' : 'downvotes') . ' + 1'),
        'voter_ids' => array_merge($voterIds, [$userId])
    ]);

    return response()->json([
        'message' => 'Vote enregistré',
        'upvotes' => $spot->fresh()->upvotes,
        'downvotes' => $spot->fresh()->downvotes
    ]);
}
}
