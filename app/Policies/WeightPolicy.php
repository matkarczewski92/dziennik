<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Weight;

class WeightPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Weight $weight): bool
    {
        return (int) $weight->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Weight $weight): bool
    {
        return (int) $weight->user_id === (int) $user->id;
    }

    public function delete(User $user, Weight $weight): bool
    {
        return (int) $weight->user_id === (int) $user->id;
    }
}
