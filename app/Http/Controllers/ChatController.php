<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    // Envoyer un message à un user spécifique
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
    ]);

    return response()->json([
        'message' => 'Message sent successfully',
        'data' => $message
    ], 201);
}

    // Récupérer toutes les conversations du user connecté
    public function getMyConversations()
{
    $user = Auth::user();
    $conversations = Conversation::where('user_one_id', $user->id)
        ->orWhere('user_two_id', $user->id)
        ->with([
            'messages.sender.profile',
            'userOne.profile',
            'userTwo.profile'
        ])
        ->orderByDesc('updated_at')
        ->get();

    return response()->json($conversations);
}

    // Récupérer les messages d'une conversation
    public function getMessages($conversation_id)
    {
        $conversation = Conversation::with([
            'messages.sender.profile',
            'userOne.profile',
            'userTwo.profile'
        ])->findOrFail($conversation_id);
        
        return response()->json($conversation);
    }
}

