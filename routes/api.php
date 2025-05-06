<?php

use App\Http\Controllers\PasswordResetCodeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

// Controllers
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\TipController;
use App\Http\Controllers\FishingLogController;
use App\Http\Controllers\SpotController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GroupChatController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| Authentification
|--------------------------------------------------------------------------
*/
Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-code', [AuthController::class, 'verifyCode']);
Route::post('/resend-code', [AuthController::class, 'resendCode']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);

Route::post('/send-reset-code', [PasswordResetCodeController::class, 'sendResetCode']);
Route::post('/verify-reset-code', [PasswordResetCodeController::class, 'verifyResetCode']);
Route::post('/update-password', [PasswordResetCodeController::class, 'updatePassword']);

/*
|--------------------------------------------------------------------------
| Routes Protégées (Authentifiées)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Authentification
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', fn (Request $request) => $request->user());

    // Utilisateurs
    Route::get('users', [AuthController::class, 'getAllUsers']);
    Route::get('user/{id}', [AuthController::class, 'CheckUser']);
    Route::get('showProfile/{id}', [AuthController::class, 'showProfile']);

    // Profils
    Route::prefix('profile')->group(function () {
        Route::get('/user/{userId}', [ProfileController::class, 'show']);
        Route::post('/', [ProfileController::class, 'update']);
        Route::get('/{id}', [ProfilController::class, 'show']);
        Route::put('/{id}', [ProfilController::class, 'update']);
    });

    // Publications (Posts)
    Route::prefix('posts')->group(function () {
        Route::get('/', [PostController::class, 'index']);
        Route::post('/', [PostController::class, 'store']);
        Route::get('/{post}', [PostController::class, 'show']);
        Route::put('/{post}', [PostController::class, 'update']);
        Route::delete('/{post}', [PostController::class, 'destroy']);
        Route::get('/other', [PostController::class, 'getOtherUsersPosts']);

        // Likes
        Route::post('/{postId}/like', [LikeController::class, 'likePost']);
        Route::get('/{postId}/liked-users', [LikeController::class, 'likedUsers']);

        // Commentaires
        Route::get('/{postId}/comments', [CommentController::class, 'index']);
        Route::post('/{postId}/comments', [CommentController::class, 'store']);
        Route::delete('/{postId}/comments/{commentId}', [CommentController::class, 'destroy']);

        // Partages
        Route::post('/{postId}/share', [ShareController::class, 'sharePost']);
    });

    // Produits & Panier
    Route::apiResource('products', ProductController::class);
    Route::post('products/{productId}/add-to-cart', [ProductController::class, 'addToCartFromProduct']);
    Route::get('orders',[ProductController::class, 'placeOrder']);
    Route::get('/products/{product}/check-stock/{quantity}', [ProductController::class, 'checkStock']);

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/add', [CartController::class, 'addToCart']);
        Route::put('/{cart}', [CartController::class, 'update']);
        Route::delete('/{cart}', [CartController::class, 'removeFromCart']);
        Route::post('/checkout', [ProductController::class, 'placeOrder']);
    });

    // order
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']); // Pour fetchOrders() (Flutter)
        Route::post('/', [OrderController::class, 'store']); // Pour createOrder() (Flutter)
        Route::get('/{id}', [OrderController::class, 'show']); // Optionnel : détail d'une commande
        Route::put('/{id}', [OrderController::class, 'update']); // Optionnel : mise à jour
        Route::delete('/{id}', [OrderController::class, 'destroy']); // Optionnel : suppression
    });


    // Événements
    Route::apiResource('events', EventController::class);
    Route::apiResource('events.participants', ParticipantController::class)
        ->scoped()
        ->except('update');
    Route::post('events/{event}/participants', [ParticipantController::class, 'store']);
    Route::post('/events/{event}/join', [EventController::class, 'joinEvent']);


    // Conseils (Tips)
    Route::apiResource('tips', TipController::class);
    Route::put('/tips/{id}', [TipController::class, 'update']);

    // Pêche (Logs & Spots)
    Route::apiResource('logs', FishingLogController::class);
    Route::apiResource('spots', SpotController::class);

    // Chat
    Route::prefix('conversations')->group(function () {
        Route::get('/', [ChatController::class, 'getMyConversations']);
        Route::get('/{id}', [ChatController::class, 'getMessages']);
        Route::post('/{conversation_id}/mark-as-read', [ChatController::class, 'markAsRead']);
        Route::get('/{id}/unread-count', [ChatController::class, 'getUnreadCount']);
    });
    Route::post('/message/send/{receiver_id}', [ChatController::class, 'send']);

    // Chat de groupe
    Route::prefix('group')->group(function () {
        Route::post('/create', [GroupChatController::class, 'createGroup']);
        Route::post('/{groupId}/add-user', [GroupChatController::class, 'addUserToGroup']);
        Route::get('/my-groups', [GroupChatController::class, 'getMyGroups']);
        Route::get('/{groupId}/messages', [GroupChatController::class, 'getGroupMessages']);
        Route::post('/{groupId}/send', [GroupChatController::class, 'sendGroupMessage']);
        Route::post('/{groupId}/mark-as-read', [GroupChatController::class, 'markGroupMessagesAsRead']);
        Route::get('/{groupId}/unread-count', [GroupChatController::class, 'getGroupUnreadCount']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // Broadcast Auth
    Route::post('/broadcasting/auth', function (Request $request) {
        return Broadcast::auth($request);
    });
});

/*
|--------------------------------------------------------------------------
| Routes Publiques
|--------------------------------------------------------------------------
*/
Route::apiResource('logs', FishingLogController::class)->only(['index', 'show']);
Route::apiResource('spots', SpotController::class)->only(['index', 'show']);
Route::apiResource('tips', TipController::class)->only(['index', 'show']);
Route::apiResource('products', ProductController::class)->only(['index', 'show']);
Route::apiResource('events', EventController::class)->only(['index', 'show']);
