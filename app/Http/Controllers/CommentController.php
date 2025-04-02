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

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $comment
        ], 201);
    }

    public function index($id)
    {
        // Vérifier que le post existe
        $post = Post::findOrFail($id);

        // Récupérer les commentaires avec les informations des utilisateurs
        $comments = $post->comments()->with('user')->get();

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
