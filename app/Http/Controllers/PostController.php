<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use App\Events\NotificationEvent;
use App\Models\User;

class PostController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show'])
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Post::with('user.profile', 'likes')->get()->map(function ($post) {
            return [
                'id' => (string) $post->id, // Convertir en string
                'user' => [
                    'id' => (string) $post->user->id, // Inclure l'objet User
                    'name' => $post->user->name, 
                    'avatar' => $post->user->profile->avatar ?? null, // Inclure l'avatar
                ],
                'post_text' => $post->post_text ?? '',
                'post_image' => $post->post_image ?? '',
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
                'like_count' => $post->likes->count(), // Ajouter le nombre de likes
                'is_liked' => $post->likes->contains('user_id', Auth::id()), // Vérifier si l'utilisateur a liké
            ];
        }));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'post_text' => 'nullable|string',
            'post_image' => 'nullable|string',
        ]);

        if (empty($fields['post_text']) && empty($fields['post_image'])) {
            return response()->json(['error' => 'Un post doit contenir du texte ou une image.'], 400);
        }

        $post = $request->user()->posts()->create($fields);
         // 🔔 Notification à tous les utilisateurs sauf l’auteur
    $sender = $request->user();
    $usersToNotify = User::where('id', '!=', $sender->id)->get();

    foreach ($usersToNotify as $user) {
        $notif = Notification::create([
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
            'message' => $sender->name . ' a ajouté un nouveau post ',
            'type' => 'post',
            'post_id' => $post->id, // Ajoute ce champ dans ta table `notifications` si nécessaire
        ]);

        broadcast(new NotificationEvent($notif))->toOthers(); // Pour ne pas envoyer au sender
    }

        return response()->json([
            'id' => (string) $post->id,
            'user' => [
                'id' => (string) $post->user->id,
                'name' => $post->user->name,
                'avatar' => $post->user->profile->avatar ?? null, // Inclure l'avatar
            ],
            'post_text' => $post->post_text ?? '', // Remplacer null par une chaîne vide
            'post_image' => $post->post_image ?? '', // Remplacer null par une chaîne vide
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'like_count' => $post->likes->count(),
            'is_liked' => $post->likes->contains('user_id', Auth::id()),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        return response()->json([
            'id' => (string) $post->id,
            'user' => [
                'id' => (string) $post->user->id,
                'name' => $post->user->name,
                'avatar' => $post->user->profile->avatar ?? null, // Inclure l'avatar
            ],
            'post_text' => $post->post_text ?? '',
            'post_image' => $post->post_image ?? '',
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'like_count' => $post->likes->count(),
            'is_liked' => $post->likes->contains('user_id', Auth::id()),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        Gate::authorize('modify', $post);

        $fields = $request->validate([
            'post_text' => 'nullable|string',
            'post_image' => 'nullable|string',
        ]);

        if (empty($fields['post_text']) && empty($fields['post_image'])) {
            return response()->json(['error' => 'Un post doit contenir du texte ou une image.'], 400);
        }

        $post->update($fields);

        return response()->json([
            'id' => (string) $post->id,
            'user' => [
                'id' => (string) $post->user->id,
                'name' => $post->user->name,
                'avatar' => $post->user->profile->avatar ?? null, // Inclure l'avatar
            ],
            'post_text' => $post->post_text ?? '',
            'post_image' => $post->post_image ?? '',
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'like_count' => $post->likes->count(),
            'is_liked' => $post->likes->contains('user_id', Auth::id()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        Gate::authorize('modify', $post);
        $post->delete();

        return response()->json(['message' => 'Le post a été supprimé.']);
    }

    /**
     * Like or Unlike a post.
     */
    public function like(Post $post)
    {
        $user = Auth::user();
        $like = Like::where('user_id', $user->id)->where('post_id', $post->id)->first();

        if ($like) {
            $like->delete();
            return response()->json(['message' => 'Post unliked'], 200);
        } else {
            Like::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
            return response()->json(['message' => 'Post liked'], 200);
        }
    }

    /**
     * Get likers and like count of a post.
     */
    public function likers(Post $post)
    {
        $likers = $post->likes()->with('user')->get();
        $likeCount = $post->likes()->count();

        return response()->json([
            'likers' => $likers,
            'like_count' => $likeCount,
        ], 200);
    }
}
