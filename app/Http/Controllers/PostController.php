<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Models\Like;


class PostController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return[
            new Middleware('auth:sanctum',except:['index','show'])
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Post::all();
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

    // Vérifier qu'au moins un des champs est rempli
    if (empty($fields['post_text']) && empty($fields['post_image'])) {
        return response()->json(['error' => 'Un post doit contenir du texte ou une image.'], 400);
    }

    $post = $request->user()->posts()->create($fields);

    return response()->json($post, 201);
}


    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        return  $post;
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

    // Vérifier qu'au moins un des champs est rempli
    if (empty($fields['post_text']) && empty($fields['post_image'])) {
        return response()->json(['error' => 'Un post doit contenir du texte ou une image.'], 400);
    }

    $post->update($fields);

    return response()->json($post);
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        Gate::authorize('modify', $post);
        $post->delete();

        return ['message' => 'the post was deleted'];
    }
    public function like(Post $post)
    {
        $user = Auth::user(); // Récupérer l'utilisateur authentifié

        // Vérifier si l'utilisateur a déjà liké ce post
        $like = Like::where('user_id', $user->id)->where('post_id', $post->id)->first();

        if ($like) {
            // Si le like existe déjà, le supprimer pour unliker
            $like->delete();
            return response()->json(['message' => 'Post unliked'], 200);
        } else {
            // Sinon, ajouter un like
            Like::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
            return response()->json(['message' => 'Post liked'], 200);
        }
    }

    // Méthode pour récupérer la liste des likers et le nombre total de likes d'un post
    public function likers(Post $post)
    {
        // Récupérer les utilisateurs qui ont liké le post
        $likers = $post->likes()->with('user')->get();

        // Récupérer le nombre total de likes
        $likeCount = $post->likes()->count();

        return response()->json([
            'likers' => $likers,
            'like_count' => $likeCount,
        ], 200);
    }

    // Les autres méthodes restent inchangées...
}

