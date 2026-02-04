<?php

namespace App\Policies;

use App\Models\Feeding;
use App\Models\User;

class FeedingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Feeding $feeding): bool
    {
        return (int) $feeding->user_id === (int) $user->id;
    }

    public function create(User $user, int $animalUserId): bool
    {
        return (int) $animalUserId === (int) $user->id;
    }

    public function update(User $user, Feeding $feeding): bool
    {
        return (int) $feeding->user_id === (int) $user->id;
    }

    public function delete(User $user, Feeding $feeding): bool
    {
        return (int) $feeding->user_id === (int) $user->id;
    }
}
