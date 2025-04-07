<?php
// app/Http/Controllers/ProfileController.php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;

class ProfileController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum')
        ];
    }

    public function show($userId)
    {
        // Vérifier si l'utilisateur est authentifié et s'il demande son propre profil
        if (Auth::check() && Auth::user()->id == $userId) {
            return response()->json(Auth::user()->load('profile'));
        }

        // Sinon, permettre l'accès au profil d'un autre utilisateur
        $user = User::find($userId);

        // Vérifier si l'utilisateur existe
        if ($user) {
            return response()->json($user->load('profile'));
        }

        // Si l'utilisateur n'existe pas
        return response()->json(['message' => 'Utilisateur non trouvé'], 404);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $profile = $user->profile;

        // Valider les données
        $request->validate([
            'name' => 'nullable|string|max:255|unique:users,name,' . $user->id,
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validation d'image
            'bio' => 'nullable|string',
        ]);

        // Mise à jour du nom d'utilisateur
        if ($request->has('name') && $request->name !== $user->name) {
            $user->name = $request->name;
        }

        // Mise à jour de l'email
        if ($request->has('email') && $request->email !== $user->email) {
            $user->email = $request->email;
        }

        // Mise à jour de l'avatar si un fichier est envoyé
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $profile->avatar = asset('storage/' . $path);
            $profile->save();  // Sauvegarde du profil

        }

        // Mise à jour de la biographie
        if ($request->has('bio')) {
            $profile->bio = $request->bio;
        }

        // Sauvegarde du profil
        $profile->save();
        $user->save();

        return response()->json([
            'message' => 'Profil mis à jour',
            'profile' => $profile,
            'user' => $user, // Inclure les informations mises à jour de l'utilisateur
        ]);
    }
}
