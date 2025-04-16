<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

Broadcast::channel('chat.chat.{conversation_id}', function ($user, $conversation_id) {
    $conversation = Conversation::find($conversation_id);
    return $conversation &&
        ($user->id === $conversation->user_one_id || $user->id === $conversation->user_two_id);
});
Broadcast::channel('group.group.{groupId}', function ($user, $groupId) {
    return $user->groupConversations()->where('group_conversations.id', $groupId)->exists();
});
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    Log::info('Vérification de l\'abonnement: Utilisateur ID: ' . $user->id . ' Canal ID: ' . $userId);
    return (int) $user->id === (int) $userId;
});



