<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;


class AuthController extends Controller
{
    public function me(): JsonResponse
    {
        $user = Auth::user()->load('profile'); // Charge aussi le profil

        return response()->json($user);
    }
    public function getAllUsers()
{
    $users = User::with('profile')->get(); // <-- charge aussi les profils
    return response()->json($users, 200);
}

public function CheckUser($id)
{
    $user = User::with('profile')->find($id); // 👈 charge aussi le profil
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    return response()->json($user);
}

public function register(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
    ]);


    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password)
    ]);
    // 💡 Création automatique du profil lié à l'utilisateur
    $profile = $user->profile()->create(); // utilisera les valeurs par défaut définies dans ta migration

    // Créer un token pour le user
    $token = $user->createToken('auth_token')->plainTextToken;

    event(new Registered($user));

    //return redirect()->route('profile')   : pour aller toul lel page ele nheb aliha (profile)

    return response()->json(
        [
            'message' => 'User registered successfully',
            'User' => $user,
            'Profile' => $profile,
            'Token' => $token
        ],
        201
    );
}

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        //verifier si l email et le password existe dans la data base
        if (!Auth::attempt($request->only('email', 'password')))
            return response()->json(
                [
                    'message' => 'invalid email or password',
                ],
                401
            );
        //comparaison entre l'email de user et l'email dans la request ele jet
        $user = User::where('email', $request->email)->FirstOrFail();
        //creer le token de $user
        $token = $user->createToken('auth_token')->plainTextToken;
        $profile = $user->load('profile');
        return response()->json(
            [
                'message' => 'login successful',
                'User' => $user,
                'Token' => $token,
            ],
            201
        );
    }
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return[
            'message '=> 'Logged out.'
                ];
    }

}
