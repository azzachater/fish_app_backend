<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PasswordResetCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Mail\EmailVerification;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetCodeMail;

class PasswordResetCodeController extends Controller
{
    public function sendResetCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'Aucun utilisateur trouvé.'], 404);
        }

        $code = rand(100000, 999999);

        // Crée ou met à jour le code
        PasswordResetCode::updateOrCreate(
            ['email' => $request->email],
            ['code' => $code, 'created_at' => now()]
        );

        // Envoie le mail
        Mail::to($request->email)->send(new ResetCodeMail($code));

        return response()->json(['message' => 'Code envoyé avec succès.']);
    }


    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string'
        ]);

        $reset = PasswordResetCode::where('email', $request->email)
            ->where('code', $request->code)
            ->first();

        if (!$reset || $reset->isExpired()) {
            return response()->json(['error' => 'Code invalide ou expiré.'], 400);
        }

        return response()->json(['message' => 'Code valide.']);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé.'], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Supprimer le code après réinitialisation
        PasswordResetCode::where('email', $request->email)->delete();

        return response()->json(['message' => 'Mot de passe mis à jour.']);
    }
}