<?php

namespace App\Policies;

use App\Models\Shed;
use App\Models\User;

class ShedPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Shed $shed): bool
    {
        return (int) $shed->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Shed $shed): bool
    {
        return (int) $shed->user_id === (int) $user->id;
    }

    public function delete(User $user, Shed $shed): bool
    {
        return (int) $shed->user_id === (int) $user->id;
    }
}
