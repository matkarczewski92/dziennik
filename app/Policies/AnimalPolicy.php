<?php

namespace App\Policies;

use App\Models\Animal;
use App\Models\User;

class AnimalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Animal $animal): bool
    {
        return (int) $animal->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Animal $animal): bool
    {
        return (int) $animal->user_id === (int) $user->id;
    }

    public function delete(User $user, Animal $animal): bool
    {
        return (int) $animal->user_id === (int) $user->id;
    }
}
