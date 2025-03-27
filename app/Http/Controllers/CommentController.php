<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Spot;

class CommentController extends Controller
{
    public function store(Request $request, $type, $id)
    {
        // Validation des données
        $request->validate([
            'content' => 'required|string',
        ]);

        // Vérifier si le type est correct et récupérer l'entité correspondante
        $commentable = null;
        if ($type === 'spot') {
            $commentable = Spot::findOrFail($id);
        } elseif ($type === 'post') {
            $commentable = Post::findOrFail($id);
        } else {
            return response()->json(['error' => 'Invalid type'], 400);
        }

        // Créer un commentaire lié à l'entité (Post ou Spot)
        $comment = $commentable->comments()->create([
            'content' => $request->content,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $comment
        ], 201);
    }

    public function index($type, $id)
    {
        // Vérifier si le type est correct et récupérer l'entité correspondante
        if ($type === 'spot') {
            $commentable = Spot::findOrFail($id);
        } elseif ($type === 'post') {
            $commentable = Post::findOrFail($id);
        } else {
            return response()->json(['error' => 'Invalid type'], 400);
        }

        // Récupérer les commentaires avec les informations des utilisateurs
        $comments = $commentable->comments()->with('user')->get();

        return response()->json($comments, 200);
    }

    public function destroy($type, $id, $commentId)
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
