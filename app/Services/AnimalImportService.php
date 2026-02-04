<?php

namespace App\Services;

use App\Exceptions\HodowlaApiException;
use App\Models\Animal;
use App\Models\AnimalGenotype;
use App\Models\AnimalGenotypeCategory;
use App\Models\AnimalSpecies;
use App\Models\Feed;
use App\Models\Feeding;
use App\Models\Photo;
use App\Models\Shed;
use App\Models\User;
use App\Models\Weight;
use App\Services\Animal\AnimalEventProjector;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AnimalImportService
{
    protected AnimalEventProjector $eventProjector;

    public function __construct(
        protected HodowlaApiClient $apiClient,
        ?AnimalEventProjector $eventProjector = null,
    ) {
        $this->eventProjector = $eventProjector ?? app(AnimalEventProjector::class);
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
        $data = $this->extractDataSection($payload);
        $animalData = Arr::get($data, 'animal');

        if (! is_array($animalData)) {
            throw new HodowlaApiException('API zwrocilo nieprawidlowy format danych. Brak payload.animal.');
        }

        $normalizedSecretTag = trim((string) Arr::get($animalData, 'secret_tag', $secretTag));
        if ($normalizedSecretTag === '') {
            $normalizedSecretTag = trim($secretTag);
        }

        $weightsPayload = $this->arrayRecords(Arr::get($data, 'weights'));
        $currentWeightFromWeights = $this->latestWeightValue($weightsPayload);

        $name = $this->sanitizeText((string) Arr::get($animalData, 'name', ''));
        if ($name === '') {
            $name = $this->sanitizeText((string) Arr::get($animalData, 'second_name', $normalizedSecretTag));
        }

        $mapped = [
            'name' => $name !== '' ? $name : $normalizedSecretTag,
            'species_id' => $this->resolveSpeciesId(
                Arr::get($animalData, 'animal_type.name'),
                Arr::get($animalData, 'animal_type.id'),
            ),
            'morph' => Arr::get($animalData, 'morph'),
            'sex' => $this->normalizeSex(Arr::get($animalData, 'sex')),
            'hatch_date' => $this->normalizeDate(Arr::get($animalData, 'date_of_birth')),
            'acquired_at' => null,
            'feeding_interval_days' => max(1, $this->toInt(Arr::get($animalData, 'feeding_interval_days', 14), 14)),
            'current_weight_grams' => $currentWeightFromWeights,
            'secret_tag' => $normalizedSecretTag,
            'remote_id' => is_numeric(Arr::get($animalData, 'id')) ? (string) ((int) Arr::get($animalData, 'id')) : '',
            'imported_from_api' => true,
            'api_snapshot' => $payload,
            'notes' => Arr::get($animalData, 'notes'),
        ];

        $animal = DB::transaction(function () use ($user, $normalizedSecretTag, $mapped, $data): Animal {
            $animal = Animal::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'secret_tag' => $normalizedSecretTag,
                ],
                $mapped,
            );

            $this->syncGenetics($animal, $this->arrayRecords(Arr::get($data, 'genetics')));
            $this->syncFeedings($animal, $this->arrayRecords(Arr::get($data, 'feedings')));
            $this->syncWeights($animal, $this->arrayRecords(Arr::get($data, 'weights')));
            $this->syncSheds($animal, $this->arrayRecords(Arr::get($data, 'sheds')));
            $this->syncPhotos($animal, $this->arrayRecords(Arr::get($data, 'gallery')));
            $this->refreshAggregates($animal);
            $this->eventProjector->rebuildForAnimal($animal);

            return $animal;
        });

        return $animal->refresh();
    }

    protected function extractDataSection(array $responseJson): array
    {
        $payload = Arr::get($responseJson, 'data');
        if (! is_array($payload)) {
            $payload = $responseJson;
        }
        $payload = $this->normalizePayloadKeys($payload);

        $requiredArrayKeys = ['genetics', 'feedings', 'weights', 'sheds', 'litters', 'gallery'];

        if (! is_array(Arr::get($payload, 'animal'))) {
            throw new HodowlaApiException('API zwrocilo nieprawidlowy format danych. Brak payload.animal.');
        }

        foreach ($requiredArrayKeys as $key) {
            if (! array_key_exists($key, $payload) || ! is_array($payload[$key])) {
                throw new HodowlaApiException("API zwrocilo nieprawidlowy format danych. Oczekiwano payload.{$key} jako tablicy.");
            }
        }

        return $payload;
    }

    protected function normalizePayloadKeys(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            $normalizedKey = is_string($key)
                ? preg_replace('/\s+/', '_', trim($key))
                : $key;

            $normalized[$normalizedKey] = $this->normalizePayloadKeys($item);
        }

        return $normalized;
    }

    protected function resolveSpeciesId(?string $speciesName, mixed $speciesId): ?int
    {
        $speciesId = is_numeric($speciesId) ? (int) $speciesId : null;
        $speciesName = trim((string) $speciesName);

        if ($speciesId) {
            $species = AnimalSpecies::query()->find($speciesId);
            if ($species) {
                if ($speciesName !== '' && $species->name !== $speciesName) {
                    $species->forceFill(['name' => $speciesName])->save();
                }

                return $species->id;
            }

            DB::table('animal_species')->insert([
                'id' => $speciesId,
                'name' => $speciesName !== '' ? $speciesName : 'Nieznany gatunek',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $speciesId;
        }

        if ($speciesName === '') {
            return null;
        }

        return AnimalSpecies::query()->firstOrCreate([
            'name' => $speciesName,
        ])->id;
    }

    protected function normalizeSex(mixed $sex): string
    {
        if (is_numeric($sex)) {
            return match ((int) $sex) {
                2 => 'male',
                3 => 'female',
                default => 'unknown',
            };
        }

        return match (strtolower(trim((string) $sex))) {
            '2', 'm', 'male', 'samiec' => 'male',
            '3', 'f', 'female', 'samica' => 'female',
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

    protected function syncGenetics(Animal $animal, array $records): void
    {
        foreach ($records as $record) {
            $category = $this->resolveGenotypeCategory(Arr::get($record, 'category'));
            if (! $category) {
                continue;
            }

            AnimalGenotype::query()->updateOrCreate(
                [
                    'animal_id' => $animal->id,
                    'genotype_id' => $category->id,
                ],
                [
                    'type' => $this->normalizeGenotypeType(Arr::get($record, 'type')),
                ],
            );
        }
    }

    protected function syncFeedings(Animal $animal, array $records): void
    {
        foreach ($records as $record) {
            $fedAt = $this->normalizeDate((string) Arr::get($record, 'created_at'));
            if (! $fedAt) {
                continue;
            }

            $feed = $this->resolveFeed(
                Arr::get($record, 'feed_name'),
                Arr::get($record, 'feed_id'),
            );

            $quantity = max(1, $this->toInt(Arr::get($record, 'amount', 1), 1));
            $prey = $this->sanitizeText((string) Arr::get($record, 'feed_name', ''));
            if ($prey === '') {
                $prey = $feed?->name ?: 'Karmowka';
            }

            Feeding::query()->updateOrCreate(
                [
                    'user_id' => $animal->user_id,
                    'animal_id' => $animal->id,
                    'fed_at' => $fedAt,
                    'feed_id' => $feed?->id,
                    'quantity' => $quantity,
                    'prey' => $prey,
                ],
                [
                    'prey_weight_grams' => null,
                    'notes' => null,
                ],
            );
        }
    }

    protected function syncWeights(Animal $animal, array $records): void
    {
        foreach ($records as $record) {
            $measuredAt = $this->normalizeDate((string) Arr::get($record, 'created_at'));
            $value = $this->normalizeDecimal(Arr::get($record, 'value'));

            if (! $measuredAt || $value === null) {
                continue;
            }

            Weight::query()->updateOrCreate(
                [
                    'user_id' => $animal->user_id,
                    'animal_id' => $animal->id,
                    'measured_at' => $measuredAt,
                    'weight_grams' => $value,
                ],
                [
                    'notes' => null,
                ],
            );
        }
    }

    protected function syncSheds(Animal $animal, array $records): void
    {
        foreach ($records as $record) {
            $shedAt = $this->normalizeDate((string) Arr::get($record, 'created_at'));
            if (! $shedAt) {
                continue;
            }

            Shed::query()->updateOrCreate(
                [
                    'user_id' => $animal->user_id,
                    'animal_id' => $animal->id,
                    'shed_at' => $shedAt,
                ],
                [
                    'quality' => null,
                    'notes' => null,
                ],
            );
        }
    }

    protected function syncPhotos(Animal $animal, array $records): void
    {
        foreach ($records as $record) {
            $url = trim((string) Arr::get($record, 'url', ''));
            if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $importMeta = [
                'source' => 'api_gallery',
                'remote_photo_id' => is_numeric(Arr::get($record, 'id')) ? (int) Arr::get($record, 'id') : null,
                'is_main' => is_numeric(Arr::get($record, 'is_main')) ? (int) Arr::get($record, 'is_main') : null,
                'banner_position' => is_numeric(Arr::get($record, 'banner_position')) ? (int) Arr::get($record, 'banner_position') : null,
                'website' => is_numeric(Arr::get($record, 'website')) ? (int) Arr::get($record, 'website') : null,
            ];
            $notes = json_encode($importMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            Photo::query()->updateOrCreate(
                [
                    'user_id' => $animal->user_id,
                    'animal_id' => $animal->id,
                    'path' => $url,
                ],
                [
                    'mime_type' => $this->guessMimeTypeFromUrl($url),
                    'size_kb' => null,
                    'taken_at' => null,
                    'notes' => $notes ?: null,
                ],
            );
        }
    }

    protected function refreshAggregates(Animal $animal): void
    {
        $animal->forceFill([
            'last_fed_at' => Feeding::query()->where('animal_id', $animal->id)->max('fed_at'),
            'current_weight_grams' => Weight::query()
                ->where('animal_id', $animal->id)
                ->orderByDesc('measured_at')
                ->value('weight_grams'),
        ])->saveQuietly();
    }

    protected function resolveGenotypeCategory(mixed $categoryPayload): ?AnimalGenotypeCategory
    {
        if (! is_array($categoryPayload)) {
            return null;
        }

        $categoryId = is_numeric(Arr::get($categoryPayload, 'id')) ? (int) Arr::get($categoryPayload, 'id') : null;
        $name = $this->sanitizeText((string) Arr::get($categoryPayload, 'name', ''));
        $geneCode = $this->normalizeGeneCode(Arr::get($categoryPayload, 'gene_code'), $name);
        $geneType = $this->normalizeGeneType(Arr::get($categoryPayload, 'gene_type'));

        if ($categoryId) {
            DB::table('animal_genotype_category')->updateOrInsert(
                ['id' => $categoryId],
                [
                    'name' => $name !== '' ? $name : "Gen {$categoryId}",
                    'gene_code' => $geneCode,
                    'gene_type' => $geneType,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );

            return AnimalGenotypeCategory::query()->find($categoryId);
        }

        if ($name === '') {
            return null;
        }

        return AnimalGenotypeCategory::query()->firstOrCreate(
            ['name' => $name],
            [
                'gene_code' => $geneCode,
                'gene_type' => $geneType,
            ],
        );
    }

    protected function resolveFeed(mixed $feedName, mixed $feedId): ?Feed
    {
        $feedId = is_numeric($feedId) ? (int) $feedId : null;
        $feedName = $this->sanitizeText((string) $feedName);

        if ($feedId) {
            $feed = Feed::query()->find($feedId);
            if ($feed) {
                if ($feedName !== '' && $feed->name !== $feedName) {
                    $feed->forceFill(['name' => $feedName])->save();
                }

                return $feed;
            }

            DB::table('feeds')->insert([
                'id' => $feedId,
                'name' => $feedName !== '' ? $feedName : "Feed {$feedId}",
                'feeding_interval' => 0,
                'amount' => 0,
                'last_price' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return Feed::query()->find($feedId);
        }

        if ($feedName === '') {
            return null;
        }

        return Feed::query()->firstOrCreate(
            ['name' => $feedName],
            [
                'feeding_interval' => 0,
                'amount' => 0,
                'last_price' => 0,
            ],
        );
    }

    protected function normalizeGenotypeType(mixed $type): string
    {
        $value = Str::lower(trim((string) $type));

        return match ($value) {
            'v', '1', 'homo', 'homozygota', 'homozygous' => 'v',
            'h', '2', 'het', 'hetero', 'heterozygota', 'heterozygous' => 'h',
            'p', '3', 'poshet', 'possible_het', 'possible het' => 'p',
            default => 'h',
        };
    }

    protected function normalizeGeneType(mixed $type): string
    {
        $value = Str::lower(trim((string) $type));
        if ($value === '') {
            return 'r';
        }

        return match ($value) {
            '1', 'r', 'rec', 'recessive' => 'r',
            '2', 'd', 'dom', 'dominant' => 'd',
            default => Str::substr($value, 0, 2),
        };
    }

    protected function normalizeGeneCode(mixed $code, string $name): string
    {
        $code = strtoupper(trim((string) $code));
        if ($code !== '') {
            return Str::substr($code, 0, 10);
        }

        $normalizedName = preg_replace('/[^a-z0-9]/i', '', $name) ?: 'GENE';

        return Str::substr(strtoupper($normalizedName), 0, 10);
    }

    protected function normalizeDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    protected function latestWeightValue(array $weights): ?float
    {
        $latestDate = null;
        $latestValue = null;

        foreach ($weights as $weight) {
            $weightDate = Arr::get($weight, 'created_at');
            $weightValue = $this->normalizeDecimal(Arr::get($weight, 'value'));
            if (! is_string($weightDate) || $weightValue === null) {
                continue;
            }

            try {
                $parsed = Carbon::parse($weightDate);
            } catch (\Throwable) {
                continue;
            }

            if ($latestDate === null || $parsed->greaterThan($latestDate)) {
                $latestDate = $parsed;
                $latestValue = $weightValue;
            }
        }

        return $latestValue;
    }

    protected function arrayRecords(mixed $records): array
    {
        if (! is_array($records)) {
            return [];
        }

        return array_values(array_filter($records, static fn (mixed $row): bool => is_array($row)));
    }

    protected function sanitizeText(string $value): string
    {
        return trim(strip_tags($value));
    }

    protected function toInt(mixed $value, int $default = 0): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }

    protected function guessMimeTypeFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => null,
        };
    }
}
