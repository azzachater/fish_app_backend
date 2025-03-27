<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Event;
use Illuminate\Auth\Access\Response;

class EventPolicy
{
    
    public function modify(User $user, Event $event): Response
    {
        return $user->id === $event->user_id
            ?Response::allow()
            :Response::deny('You do not own this event');
    }
}
