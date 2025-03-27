<?php

namespace App\Http\Controllers;

//use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Pour gérer l'authentification

class LikeController extends Controller
{
    /**
     * Liker ou unliker un post
     */
    public function likePost(Request $request, Post $postId)
    {
        // Récupérer le post
        $post = Post::findOrFail($postId);

        // Vérifier si l'utilisateur a déjà liké le post
        $like = Like::where('user_id', Auth::id())->where('post_id', $postId)->first();

        if ($like) {
            // Supprimer le like (unlike)
            $like->delete();
            $message = 'Post unliked';
        } else {
            // Ajouter un like
            Like::create([
                'user_id' => Auth::id(),
                'post_id' => $postId,
            ]);
            $message = 'Post liked';
        }

        // Compter le nombre total de likes du post après l'action
        $likeCount = Like::where('post_id', $postId)->count();

        return response()->json([
            'message' => $message,
            'likeCount' => $likeCount,
        ]);
    }

    /**
     * Récupérer la liste des utilisateurs ayant liké un post (uniquement accessible au propriétaire du post)
     */
    public function likedUsers($postId)
    {
        // Récupérer le post
        $post = Post::findOrFail($postId);

        // Vérifier si l'utilisateur connecté est le propriétaire du post
        if ($post->user_id !== Auth::id()) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        // Récupérer la liste des utilisateurs ayant liké le post
        $likedUsers = Like::where('post_id', $postId)
            ->with('user:id,name,email') // Charger les informations des utilisateurs
            ->get()
            ->pluck('user'); // Extraire uniquement les utilisateurs

        return response()->json([
            'totalLikes' => $likedUsers->count(),
            'likedUsers' => $likedUsers,
        ]);
    }
}
