<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Share;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShareController extends Controller
{
    public function sharePost(Request $request, $postId)
    {
        // Vérifier si l'utilisateur est authentifié
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Valider la requête
        $request->validate([
            'share_text' => 'nullable|string|max:500', 
        ]);

        // Vérifier si le post existe
        $post = Post::find($postId);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        // Vérifier si l'utilisateur a déjà partagé ce post
        $share = Share::where('user_id', Auth::id())
                      ->where('post_id', $postId)
                      ->first();

        if ($share) {
            return response()->json(['message' => 'Post already shared'], 409);
        }

        // Créer un nouveau partage
        $share = Share::create([
            'user_id' => Auth::id(),
            'post_id' => $postId,
            'share_text' => $request->input('share_text'), // Texte personnalisé
        ]);

        return response()->json(['message' => 'Post shared successfully'], 201);
    }
}

