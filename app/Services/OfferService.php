<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalOffer;
use App\Models\User;
use App\Services\Animal\AnimalEventProjector;
use Illuminate\Auth\Access\AuthorizationException;

class OfferService
{
    public function __construct(
        protected AnimalEventProjector $eventProjector,
    ) {
    }

    public function save(User $user, Animal $animal, array $data, ?AnimalOffer $offer = null): AnimalOffer
    {
        $this->ensureOwnership($user, $animal);

        $payload = [
            'price' => $data['price'],
            'sold_date' => $data['sold_date'] ?? null,
        ];

        if ($offer) {
            if ((int) $offer->animal_id !== (int) $animal->id) {
                throw new AuthorizationException();
            }

            $offer->update($payload);
        } else {
            $offer = AnimalOffer::query()->create([
                'animal_id' => $animal->id,
                ...$payload,
            ]);
        }

        $this->eventProjector->projectOffer($offer);

        return $offer->refresh();
    }

    public function delete(User $user, AnimalOffer $offer): void
    {
        $animal = $offer->animal;
        if (! $animal || (int) $animal->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }

        $offer->delete();
        $this->eventProjector->removeOffer($offer);
    }

    protected function ensureOwnership(User $user, Animal $animal): void
    {
        if ((int) $animal->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }
    }
}

