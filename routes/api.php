<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LikeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;
use App\Http\Controllers\FishingLogController;
use App\Http\Controllers\TipController;
use App\Http\Controllers\SpotController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\ProfilController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth: sanctum');

Route::apiResource('posts', PostController::class)->middleware('auth:sanctum');

Route::get('posts/other', [PostController::class, 'getOtherUsersPosts'])->middleware('auth:sanctum');

Route::get('user/{id}', [AuthController::class, 'CheckUser']);

Route::apiResource('products', ProductController::class)->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->group(function () {
    Route::post('products/{productId}/add-to-cart', [ProductController::class, 'addToCartFromProduct']);
});
// Routes protégées pour la gestion du panier
Route::middleware('auth:sanctum')->group(function () {
    Route::post('cart/add', [CartController::class, 'addToCart']); // Ajouter un produit au panier
    Route::get('cart', [CartController::class, 'index']); // Voir les produits dans le panier
    Route::put('cart/update/{cart}', [CartController::class, 'update']); // Modifier quantité
    Route::delete('cart/remove/{cart}', [CartController::class, 'removeFromCart']); // Supprimer du panier
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('showProfile/{id}', [AuthController::class, 'showProfile']);

Route::apiResource('events', EventController::class)->middleware('auth:sanctum');
Route::apiResource('events.participants', ParticipantController::class)->scoped()->except('update')->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // Liker / Unliker un post
    Route::post('/posts/{postId}/like', [LikeController::class, 'likePost']);

    // Voir la liste des utilisateurs ayant liké un post (seulement pour le propriétaire)
    Route::get('/posts/{postId}/liked-users', [LikeController::class, 'likedUsers']);
});

Route::apiResource('logs', FishingLogController::class);
Route::apiResource('tips', TipController::class)->middleware('auth:sanctum');
Route::apiResource('spots', SpotController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/{type}/{id}/comments', [CommentController::class, 'store'])
        ->where('type', 'spot|post'); // Ajouter un commentaire

    Route::get('/{type}/{id}/comments', [CommentController::class, 'index'])
        ->where('type', 'spot|post'); // Voir tous les commentaires d’un post ou spot

    Route::delete('/{type}/{id}/comment/{commentId}', [CommentController::class, 'destroy'])
        ->where('type', 'spot|post'); // Supprimer un commentaire
});


Route::post('/posts/{postId}/share', [ShareController::class, 'sharePost'])->middleware('auth:sanctum');
Route::get('/profil/{id}', [ProfilController::class, 'show'])->middleware('auth:sanctum');
Route::put('/profil/{id}', [ProfilController::class, 'update'])->middleware('auth:sanctum');
