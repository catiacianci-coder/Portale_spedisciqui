<?php

namespace App\Policies;

use App\Models\spedizione;
use App\Models\User;

class SpedizionePolicy
{
    public function view(User $user, spedizione $spedizione): bool
    {
        return (int) $spedizione->user_id === (int) $user->id;
    }
}
