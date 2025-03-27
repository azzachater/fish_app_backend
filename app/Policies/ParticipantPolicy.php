<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Participant;
use Illuminate\Auth\Access\Response;

class ParticipantPolicy
{
    public function modify(User $user, Participant $participant): Response
    {
        return $user->id === $participant->user_id
            ?Response::allow()
            :Response::deny('You do not own this event');
    }
}


