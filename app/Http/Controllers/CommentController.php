<?php
namespace App\Http\Controllers;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Comment;
use App\Models\Post;

class CommentController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['index'])
        ];
    }

    public function store(Request $request, $id)
    {
        // Validation des données
        $request->validate([
            'content' => 'required|string',
        ]);

        // Vérifier que le post existe
        $post = Post::findOrFail($id);

        // Créer un commentaire lié au post
        $comment = $post->comments()->create([
            'content' => $request->content,
            'user_id' => auth()->id(),
        ]);

        // Charger les relations nécessaires avant de retourner la réponse
        $comment->load(['user.profile']);

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => [
                'id' => $comment->id,
                'post_id' => $comment->post_id,
                'user_id' => $comment->user_id,
                'content' => $comment->content,
                'created_at' => $comment->created_at,
                'updated_at' => $comment->updated_at,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'email' => $comment->user->email,
                    'avatar' => $comment->user->profile->avatar ?? null,
                    'email_verified_at' => $comment->user->email_verified_at,
                    'created_at' => $comment->user->created_at,
                    'updated_at' => $comment->user->updated_at
                ]
            ]
        ], 201);
    }

    public function index($id)
    {
        // Vérifier que le post existe
        $post = Post::findOrFail($id);

        // Récupérer les commentaires avec les informations des utilisateurs et leur profil
        $comments = $post->comments()
            ->with(['user.profile'])
            ->get()
            ->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'post_id' => $comment->post_id,
                    'user_id' => $comment->user_id,
                    'content' => $comment->content,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                        'email' => $comment->user->email,
                        'avatar' => $comment->user->profile->avatar ?? null,
                        'email_verified_at' => $comment->user->email_verified_at,
                        'created_at' => $comment->user->created_at,
                        'updated_at' => $comment->user->updated_at
                    ]
                ];
            });

        return response()->json($comments, 200);
    }

    public function destroy($id, $commentId)
    {
        $comment = Comment::findOrFail($commentId);

        // Vérifier si l'utilisateur est l'auteur du commentaire
        if (Auth::id() !== $comment->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted'], 204);
    }
}