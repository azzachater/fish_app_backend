<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\FishingLog;

class FishingLogController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show'])
        ];
    }

    /**
     * Affiche la liste des journaux de pêche de l'utilisateur connecté.
     */
    public function index()
    {
        $logs = FishingLog::where('user_id', Auth::id())->get();
        return response()->json($logs);
    }

    /**
     * Affiche les détails d'un journal de pêche spécifique.
     */
    public function show($id)
    {
        $log = FishingLog::where('user_id', Auth::id())->findOrFail($id);
        return response()->json($log);
    }

    /**
     * Crée un nouveau journal de pêche.
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'required|string|max:5',
            'location' => 'required|string',
            'species_caught' => 'required|string',
            'fishing_conditions' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $fields['user_id'] = Auth::id(); // Ajout manuel de l'ID utilisateur

        $fishingLog = FishingLog::create($fields);

        return response()->json($fishingLog, 201);
    }

    /**
     * Met à jour un journal de pêche existant.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'required|string|max:5',
            'location' => 'required|string',
            'species_caught' => 'required|string',
            'fishing_conditions' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        // Récupérer le journal de l'utilisateur connecté
        $log = FishingLog::where('user_id', Auth::id())->findOrFail($id);

        // Mettre à jour les données
        $log->update($request->all());

        return response()->json($log);
    }

    /**
     * Supprime un journal de pêche.
     */
    public function destroy($id)
    {
        // Récupérer le journal de l'utilisateur connecté
        $log = FishingLog::where('user_id', Auth::id())->findOrFail($id);
        $log->delete();

        return response()->json(null, 204); // Code HTTP 204 : No Content
    }
}
