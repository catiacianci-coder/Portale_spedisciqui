<?php

namespace App\Policies;

use App\Models\ordine;
use App\Models\User;

class OrdinePolicy
{
    public function view(User $user, ordine $ordine): bool
    {
        return (int) $ordine->user_id === (int) $user->id;
    }

    public function pay(User $user, ordine $ordine): bool
    {
        return $this->view($user, $ordine)
            && $ordine->stato === ordine::STATO_NON_PAGATO;
    }

    public function update(User $user, ordine $ordine): bool
    {
        return $this->view($user, $ordine)
            && $ordine->stato === ordine::STATO_NON_PAGATO;
    }

    public function cancel(User $user, ordine $ordine): bool
    {
        return $this->view($user, $ordine)
            && $ordine->stato === ordine::STATO_NON_PAGATO;
    }
}
