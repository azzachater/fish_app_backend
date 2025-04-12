<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

Broadcast::channel('chat.{conversation_id}', function ($user, $conversation_id) {
    return $user->id === Conversation::find($conversation_id)?->user_one_id
        || $user->id === Conversation::find($conversation_id)?->user_two_id;
});

