<?php
namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageEvent;
use App\Models\Notification;
use App\Events\NotificationEvent;



class ChatController extends Controller
{
   
    public function send(Request $request, $receiver_id)
{
    $request->validate([
        'content' => 'required|string',
    ]);

    $sender_id = Auth::id();

    $conversation = Conversation::firstOrCreate(
        [
            'user_one_id' => min($sender_id, $receiver_id),
            'user_two_id' => max($sender_id, $receiver_id),
        ]
    );

    $message = Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $sender_id,
        'content' => $request->content,
        'is_read' => false, 
    ]);
    $sender = auth()->user(); // ou User::find($sender_id);
    // ✅ Créer la notification ici
    $notif = Notification::create([
        'sender_id' =>  $sender->id,
        'receiver_id' => $receiver_id,
        'message' => $sender->name . ' vous a envoyé un message.',
        'type' => 'private',
        'conversation_id' => $conversation->id,
    ]);

    $conversation->touch();
    broadcast(new NotificationEvent($notif))->toOthers();

    broadcast(new MessageEvent($message))->toOthers();
    \Log::info('Event triggered', ['message_id' => $message->id]);

    return response()->json([
        'message' => 'Message sent successfully',
        'data' => $message
    ], 201);
}

public function getMyConversations()
{
    $user = Auth::user();

    $conversations = Conversation::where('user_one_id', $user->id)
        ->orWhere('user_two_id', $user->id)
        ->with([
            'messages' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(1); // 🔽 seulement le dernier message
            },
            'messages.sender.profile',
            'userOne.profile',
            'userTwo.profile'
        ])
        ->orderByDesc('updated_at')
        ->get()
        ->map(function($conversation) use ($user) {
            $lastMessage = $conversation->messages->first();

            $unreadCount = Message::where('conversation_id', $conversation->id)
                ->where('sender_id', '!=', $user->id)
                ->where('is_read', false)
                ->count();

            return [
                'id' => $conversation->id,
                'user_one' => $conversation->userOne,
                'user_two' => $conversation->userTwo,
                'last_message' => $lastMessage,
                'unread_count' => $unreadCount
            ];
        })->values(); // 🔄 pour avoir un tableau sans index bizarres

    return response()->json($conversations, 200, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}


    public function getMessages($conversation_id)
    {
        $user = Auth::user();
        $conversation = Conversation::with([
            'messages.sender.profile',
            'userOne.profile',
            'userTwo.profile'
        ])->findOrFail($conversation_id);
        
        // Marquer les messages comme lus
        Message::where('conversation_id', $conversation_id)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
        
        return response()->json([
            'id' => $conversation->id,
            'user_one' => $conversation->userOne,
            'user_two' => $conversation->userTwo,
            'messages' => $conversation->messages,
            'unread_count' => 0 // Maintenant que c'est ouvert, plus de messages non lus
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function markAsRead($conversation_id)
    {
        $user = Auth::user();
        
        Message::where('conversation_id', $conversation_id)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
        
        return response()->json(['message' => 'Messages marked as read']);
    }
    public function getUnreadCount($conversation_id)
{
    $user = Auth::user();
    $count = Message::where('conversation_id', $conversation_id)
        ->where('sender_id', '!=', $user->id)
        ->where('is_read', false)
        ->count();
        
    return response()->json(['unread_count' => $count]);
}
}