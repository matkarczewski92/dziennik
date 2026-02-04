<?php

namespace App\Policies;

use App\Models\Animal;
use App\Models\AnimalOffer;
use App\Models\User;

class AnimalOfferPolicy
{
    public function view(User $user, AnimalOffer $offer): bool
    {
        return (int) $offer->animal?->user_id === (int) $user->id;
    }

    public function update(User $user, AnimalOffer $offer): bool
    {
        return $this->view($user, $offer);
    }

    public function delete(User $user, AnimalOffer $offer): bool
    {
        return $this->view($user, $offer);
    }

    public function create(User $user, Animal $animal): bool
    {
        return (int) $animal->user_id === (int) $user->id;
    }
}

