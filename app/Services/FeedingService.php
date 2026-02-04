<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Feed;
use App\Models\Feeding;
use App\Models\User;
use App\Services\Animal\AnimalEventProjector;
use Illuminate\Auth\Access\AuthorizationException;

class FeedingService
{
    public function __construct(
        protected AnimalEventProjector $eventProjector,
    ) {
    }

    public function create(User $user, Animal $animal, array $data): Feeding
    {
        $this->ensureOwnership($user, $animal);
        $feed = null;
        if (! empty($data['feed_id'])) {
            $feed = Feed::query()->findOrFail((int) $data['feed_id']);
        }

        $feeding = Feeding::query()->create([
            'user_id' => $user->id,
            'animal_id' => $animal->id,
            'feed_id' => $feed?->id,
            'fed_at' => $data['fed_at'],
            'prey' => $feed?->name ?? ($data['prey'] ?? 'Nieznany pokarm'),
            'prey_weight_grams' => $data['prey_weight_grams'] ?? null,
            'quantity' => (int) ($data['quantity'] ?? 1),
            'notes' => $data['notes'] ?? null,
        ]);

        $animal->forceFill([
            'last_fed_at' => $feeding->fed_at,
        ])->save();

        $this->eventProjector->projectFeeding($feeding);

        return $feeding;
    }

    public function delete(User $user, Feeding $feeding): void
    {
        if ((int) $feeding->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }

        $animal = $feeding->animal;
        $feeding->delete();

        if ($animal) {
            $animal->forceFill([
                'last_fed_at' => Feeding::query()->where('animal_id', $animal->id)->max('fed_at'),
            ])->save();
        }

        $this->eventProjector->removeFeeding($feeding);
    }

    public function update(User $user, Feeding $feeding, array $data): Feeding
    {
        if ((int) $feeding->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }

        $feed = null;
        if (! empty($data['feed_id'])) {
            $feed = Feed::query()->findOrFail((int) $data['feed_id']);
        }

        $feeding->update([
            'feed_id' => $feed?->id,
            'fed_at' => $data['fed_at'],
            'prey' => $feed?->name ?? ($data['prey'] ?? $feeding->prey),
            'prey_weight_grams' => $data['prey_weight_grams'] ?? null,
            'quantity' => (int) ($data['quantity'] ?? 1),
            'notes' => $data['notes'] ?? null,
        ]);

        $animal = $feeding->animal;
        if ($animal) {
            $animal->forceFill([
                'last_fed_at' => Feeding::query()->where('animal_id', $animal->id)->max('fed_at'),
            ])->save();
        }

        $this->eventProjector->projectFeeding($feeding);

        return $feeding->refresh();
    }

    protected function ensureOwnership(User $user, Animal $animal): void
    {
        if ((int) $animal->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }
    }
}
