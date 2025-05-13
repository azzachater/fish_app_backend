<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailVerification;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Mail\ResetCodeMail;


class AuthController extends Controller
{
    public function me(): JsonResponse
{
    /** @var \App\Models\User|null $user */
    $user = Auth::user();

    if (!$user) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    return response()->json($user->load('profile'));
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

    // generer l code ele bech ijina fel mail
    $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);


    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'verification_code' => $verificationCode,
        'verification_code_expires_at' => Carbon::now()->addHours(24),
    ]);
    // 💡 Création automatique du profil lié à l'utilisateur
    $profile = $user->profile()->create(); // utilisera les valeurs par défaut définies dans ta migration

    // Créer un token pour le user
    $token = $user->createToken('auth_token')->plainTextToken;

    //envoyer l'email de verification
    Mail::to($user->email)->send(new EmailVerification($user));

    return response()->json([
        'message' => 'Inscription réussie! Un code de vérification a été envoyé à votre email.',
        'user_id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
    ], 201);
}
public function verifyEmail(Request $request)
{
    $request->validate([
        'token' => 'required|string',
    ]);

    $user = User::where('email_verification_token', $request->token)->first();

    if (!$user) {
        return response()->json(['message' => 'Token invalide'], 404);
    }
    if ($user->email_verified_at) {
        return response()->json(['message' => 'Email déjà vérifié'], 400);
    }

    $user->email_verified_at = now();
    $user->email_verification_token = null;
    $user->save();

    return response()->json(['message' => 'Email vérifié avec succès']);
}
public function verifyCode(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'code' => 'required|string|size:6',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->verification_code !== $request->code) {
            return response()->json(['message' => 'Code invalide'], 400);
        }

        if (Carbon::now()->gt($user->verification_code_expires_at)) {
            return response()->json(['message' => 'Code expiré'], 400);
        }

        $user->email_verified_at = Carbon::now();
        $user->verification_code = null;
        $user->verification_code_expires_at = null;
        $user->save();

        return response()->json([
            'message' => 'Email vérifié avec succès',
            'token' => $user->createToken('auth_token')->plainTextToken,
        ]);
    }

    public function resendCode(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
        ]);

        $user = User::findOrFail($request->user_id);

        // Générer un nouveau code
        $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->verification_code = $verificationCode;
        $user->verification_code_expires_at = Carbon::now()->addHours(24);
        $user->save();

        Mail::to($user->email)->send(new EmailVerification($user));

        return response()->json(['message' => 'Nouveau code envoyé']);
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