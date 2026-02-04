<?php

namespace App\Services;

use App\Exceptions\HodowlaApiException;
use App\Models\Animal;
use App\Models\AnimalSpecies;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\RateLimiter;

class AnimalImportService
{
    public function __construct(
        protected HodowlaApiClient $apiClient
    ) {
    }

    public function importBySecretTag(User $user, string $secretTag): Animal
    {
        $key = "animal-import:{$user->id}";

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            throw new HodowlaApiException("Za duzo prob importu. Sprobuj ponownie za {$seconds}s.");
        }

        RateLimiter::hit($key, 60);

        $payload = $this->apiClient->fetchAnimalBySecretTag($secretTag);

        $mapped = [
            'name' => Arr::get($payload, 'name', Arr::get($payload, 'animal_name', $secretTag)),
            'species_id' => $this->resolveSpeciesId(
                Arr::get($payload, 'species'),
                Arr::get($payload, 'animal_type_id', Arr::get($payload, 'species_id')),
            ),
            'morph' => Arr::get($payload, 'morph'),
            'sex' => $this->normalizeSex(Arr::get($payload, 'sex')),
            'hatch_date' => $this->normalizeDate(Arr::get($payload, 'hatch_date', Arr::get($payload, 'birth_date'))),
            'acquired_at' => $this->normalizeDate(Arr::get($payload, 'acquired_at')),
            'feeding_interval_days' => (int) Arr::get($payload, 'feeding_interval_days', 14),
            'current_weight_grams' => Arr::get($payload, 'current_weight_grams', Arr::get($payload, 'weight_grams')),
            'secret_tag' => $secretTag,
            'remote_id' => (string) Arr::get($payload, 'id', ''),
            'imported_from_api' => true,
            'api_snapshot' => $payload,
            'notes' => Arr::get($payload, 'notes'),
        ];

        $animal = Animal::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'secret_tag' => $secretTag,
            ],
            $mapped,
        );

        return $animal->refresh();
    }

    protected function resolveSpeciesId(?string $speciesName, mixed $speciesId): ?int
    {
        $speciesId = is_numeric($speciesId) ? (int) $speciesId : null;
        if ($speciesId && AnimalSpecies::query()->whereKey($speciesId)->exists()) {
            return $speciesId;
        }

        $speciesName = trim((string) $speciesName);
        if ($speciesName === '') {
            return null;
        }

        return AnimalSpecies::query()->firstOrCreate([
            'name' => $speciesName,
        ])->id;
    }

    protected function normalizeSex(?string $sex): string
    {
        return match (strtolower((string) $sex)) {
            'm', 'male', 'samiec' => 'male',
            'f', 'female', 'samica' => 'female',
            default => 'unknown',
        };
    }

    protected function normalizeDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
