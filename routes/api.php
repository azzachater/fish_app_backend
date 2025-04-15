<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth & User
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilController;

// Posts, Likes, Comments, Shares
use App\Http\Controllers\PostController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ShareController;

// Events & Participants
use App\Http\Controllers\EventController;
use App\Http\Controllers\ParticipantController;

// Marketplace: Products & Cart
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;

// Community Features: Tips, Logs, Spots
use App\Http\Controllers\TipController;
use App\Http\Controllers\FishingLogController;
use App\Http\Controllers\SpotController;

// Chat
use App\Http\Controllers\ChatController;


// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-code', [AuthController::class, 'verifyCode']);
Route::post('/resend-code', [AuthController::class, 'resendCode']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('users', [AuthController::class, 'getAllUsers']);
Route::get('user/{id}', [AuthController::class, 'CheckUser']);
Route::get('/showProfile/{id}', [AuthController::class, 'showProfile']);
Route::get('/sanctum/csrf-cookie', function (Request $request) {
    return response()->json(['csrf_token' => csrf_token()]);
});

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // User Profile
    Route::get('/user/{userId}/profile', [ProfileController::class, 'show']);
    Route::post('/profile', [ProfileController::class, 'update']);
    Route::get('/profil/{id}', [ProfilController::class, 'show']);
    Route::put('/profil/{id}', [ProfilController::class, 'update']);

    // Posts
    Route::apiResource('posts', PostController::class);
    Route::get('posts/other', [PostController::class, 'getOtherUsersPosts']);
    Route::post('/posts/{postId}/like', [LikeController::class, 'likePost']);
    Route::get('/posts/{postId}/liked-users', [LikeController::class, 'likedUsers']);
    Route::post('/posts/{postId}/share', [ShareController::class, 'sharePost']);

    // Comments (add & delete)
    Route::post('/posts/{postId}/comments', [CommentController::class, 'store']);
    Route::delete('/posts/{postId}/comments/{commentId}', [CommentController::class, 'destroy']);

    // Logs & Spots (public access if needed)
    Route::apiResource('logs', FishingLogController::class);
    Route::apiResource('spots', SpotController::class);

    // Comments (get)
    Route::get('/posts/{postId}/comments', [CommentController::class, 'index']);

    // Products & Cart
    Route::apiResource('products', ProductController::class);
    Route::post('products/{productId}/add-to-cart', [ProductController::class, 'addToCartFromProduct']);

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/add', [CartController::class, 'addToCart']);
        Route::put('/{cart}', [CartController::class, 'update']);
        Route::delete('/{cart}', [CartController::class, 'removeFromCart']);
    });

    // Events
    Route::apiResource('events', EventController::class);
    Route::apiResource('events.participants', ParticipantController::class)->scoped()->except('update');

    // Tips
    Route::apiResource('tips', TipController::class);
    Route::put('/tips/{id}', [TipController::class, 'update']);

    // Chat
    Route::post('/message/send/{receiver_id}', [ChatController::class, 'send']);
    Route::get('/conversations', [ChatController::class, 'getMyConversations']);
    Route::get('/conversations/{id}', [ChatController::class, 'getMessages']);
    Route::post('/conversations/{conversation_id}/mark-as-read', [ChatController::class, 'markAsRead']);
    Route::get('/conversations/{id}/unread-count', [ChatController::class, 'getUnreadCount']);
});

// Authenticated user info (single user)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
