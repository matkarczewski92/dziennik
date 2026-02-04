<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\User;
use App\Models\Weight;
use App\Services\Animal\AnimalEventProjector;
use Illuminate\Auth\Access\AuthorizationException;

class WeightService
{
    public function __construct(
        protected AnimalEventProjector $eventProjector,
    ) {
    }

    public function create(User $user, Animal $animal, array $data): Weight
    {
        $this->ensureOwnership($user, $animal);

        $weight = Weight::query()->create([
            'user_id' => $user->id,
            'animal_id' => $animal->id,
            'measured_at' => $data['measured_at'],
            'weight_grams' => $data['weight_grams'],
            'notes' => $data['notes'] ?? null,
        ]);

        $animal->forceFill([
            'current_weight_grams' => $weight->weight_grams,
        ])->save();

        $this->eventProjector->projectWeight($weight);

        return $weight;
    }

    public function delete(User $user, Weight $weight): void
    {
        if ((int) $weight->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }

        $animal = $weight->animal;
        $weight->delete();

        if ($animal) {
            $animal->forceFill([
                'current_weight_grams' => Weight::query()
                    ->where('animal_id', $animal->id)
                    ->orderByDesc('measured_at')
                    ->value('weight_grams'),
            ])->save();
        }

        $this->eventProjector->removeWeight($weight);
    }

    public function update(User $user, Weight $weight, array $data): Weight
    {
        if ((int) $weight->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }

        $weight->update([
            'measured_at' => $data['measured_at'],
            'weight_grams' => $data['weight_grams'],
            'notes' => $data['notes'] ?? null,
        ]);

        $animal = $weight->animal;
        if ($animal) {
            $animal->forceFill([
                'current_weight_grams' => Weight::query()
                    ->where('animal_id', $animal->id)
                    ->orderByDesc('measured_at')
                    ->value('weight_grams'),
            ])->save();
        }

        $this->eventProjector->projectWeight($weight);

        return $weight->refresh();
    }

    protected function ensureOwnership(User $user, Animal $animal): void
    {
        if ((int) $animal->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }
    }
}
