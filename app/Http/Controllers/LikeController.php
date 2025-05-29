<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use App\Events\NotificationEvent;

class LikeController extends Controller
{
    /**
     * Liker ou unliker un post
     */
    public function likePost(Request $request, $postId)
    {
        $post = Post::findOrFail($postId);
        $user = Auth::user();

        $like = Like::where('user_id', $user->id)->where('post_id', $postId)->first();

        if ($like) {
            // Suppression du like
            $like->delete();
        } else {
            // Création du like
            Like::create([
                'user_id' => $user->id,
                'post_id' => $postId,
            ]);

            // 🔔 Notification au propriétaire du post (s'il n'est pas celui qui like)
            if ($post->user_id !== $user->id) {
                $notif = Notification::create([
                    'sender_id' => $user->id,
                    'receiver_id' => $post->user_id,
                    'message' => $user->name . ' a aimé votre post',
                    'type' => 'like',
                    'post_id' => $post->id,
                ]);

                broadcast(new NotificationEvent($notif))->toOthers();
            }
        }

        // Charger la relation profile pour accéder à l'avatar
        $post->load('user.profile')->append(['like_count', 'is_liked']);
        $post->user->avatar = $post->user->profile->avatar ?? null;

        return response()->json($post);
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