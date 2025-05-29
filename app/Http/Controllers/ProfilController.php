<?php

namespace App\Http\Controllers;
use App\Models\Profil;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfilController extends Controller
{


    public function show($userId)
    {
    // Récupérer l'utilisateur avec son profil
    $user = User::with('profil')->find($userId);

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    // Récupérer les posts partagés par l'utilisateur
    $sharedPosts = $user->shares()->with('post')->get();

    // Retourner les données
    return response()->json([
        'user' => $user,
        'profil' => $user->profil,  // Ajout du profil
        'shared_posts' => $sharedPosts,
    ]);
}


public function update(Request $request, $id)
    {
    $request->validate([
        'bio' => 'nullable|string',
        'location' => 'nullable|string',
        'avatar' => 'nullable|string',
    ]);

    $profil = Profil::where('user_id', $id)->first();

    if (!$profil) {
        return response()->json(['message' => 'Profil non trouvé'], 404);
    }

    if ($request->user()->cannot('update', $profil)) {
        return response()->json(['message' => 'Action non autorisée'], 403);
    }

    $profil->update($request->all());

    return response()->json($profil);
    }

}