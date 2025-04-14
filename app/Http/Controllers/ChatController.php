<?php
namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageEvent;

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

        $conversation->touch();

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
                    $query->orderBy('created_at', 'desc');
                },
                'messages.sender.profile',
                'userOne.profile',
                'userTwo.profile'
            ])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function($conversation) use ($user) {
                // Compter les messages non lus
                $unreadCount = $conversation->messages()
                    ->where('sender_id', '!=', $user->id)
                    ->where('is_read', false)
                    ->count();
                
                // Récupérer le dernier message
                $lastMessage = $conversation->messages->first();
                
                return [
                    'id' => $conversation->id,
                    'user_one' => $conversation->userOne,
                    'user_two' => $conversation->userTwo,
                    'last_message' => $lastMessage,
                    'unread_count' => $unreadCount,
                    'messages' => $conversation->messages
                ];
            });

        return response()->json($conversations);
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
        ]);
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