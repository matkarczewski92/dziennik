<?php

namespace App\Services\Animal;

use App\Models\Animal;
use App\Models\AnimalEvent;
use App\Models\AnimalGenotype;
use App\Models\AnimalOffer;
use App\Models\Feeding;
use App\Models\Photo;
use App\Models\Shed;
use App\Models\Weight;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnimalEventProjector
{
    public function projectFeeding(Feeding $feeding): AnimalEvent
    {
        $feeding->loadMissing('feed');

        return $this->upsertFromSource(
            animalId: (int) $feeding->animal_id,
            type: 'feeding',
            happenedAt: $feeding->fed_at ?? $feeding->created_at,
            sourceTable: 'feedings',
            sourceId: (int) $feeding->id,
            payload: [
                'feed_id' => $feeding->feed_id,
                'feed_name' => $feeding->feed?->name ?? $feeding->prey,
                'prey' => $feeding->prey,
                'quantity' => $feeding->quantity,
                'prey_weight_grams' => $feeding->prey_weight_grams,
                'notes' => $feeding->notes,
            ],
        );
    }

    public function removeFeeding(Feeding|int $feeding): void
    {
        $this->deleteFromSource('feedings', $feeding instanceof Feeding ? (int) $feeding->id : (int) $feeding);
    }

    public function projectWeight(Weight $weight): AnimalEvent
    {
        return $this->upsertFromSource(
            animalId: (int) $weight->animal_id,
            type: 'weight',
            happenedAt: $weight->measured_at ?? $weight->created_at,
            sourceTable: 'weights',
            sourceId: (int) $weight->id,
            payload: [
                'value' => $weight->weight_grams,
                'notes' => $weight->notes,
            ],
        );
    }

    public function removeWeight(Weight|int $weight): void
    {
        $this->deleteFromSource('weights', $weight instanceof Weight ? (int) $weight->id : (int) $weight);
    }

    public function projectShed(Shed $shed): AnimalEvent
    {
        return $this->upsertFromSource(
            animalId: (int) $shed->animal_id,
            type: 'shed',
            happenedAt: $shed->shed_at ?? $shed->created_at,
            sourceTable: 'sheds',
            sourceId: (int) $shed->id,
            payload: [
                'quality' => $shed->quality,
                'notes' => $shed->notes,
            ],
        );
    }

    public function removeShed(Shed|int $shed): void
    {
        $this->deleteFromSource('sheds', $shed instanceof Shed ? (int) $shed->id : (int) $shed);
    }

    public function projectGenotype(AnimalGenotype $genotype): AnimalEvent
    {
        $genotype->loadMissing('genotypeCategory');

        return $this->upsertFromSource(
            animalId: (int) $genotype->animal_id,
            type: 'genotype',
            happenedAt: $genotype->created_at,
            sourceTable: 'animal_genotype',
            sourceId: (int) $genotype->id,
            payload: [
                'genotype_id' => $genotype->genotype_id,
                'gene_name' => $genotype->genotypeCategory?->name,
                'gene_code' => $genotype->genotypeCategory?->gene_code,
                'type' => $genotype->type,
            ],
        );
    }

    public function removeGenotype(AnimalGenotype|int $genotype): void
    {
        $this->deleteFromSource('animal_genotype', $genotype instanceof AnimalGenotype ? (int) $genotype->id : (int) $genotype);
    }

    public function projectPhoto(Photo $photo): AnimalEvent
    {
        return $this->upsertFromSource(
            animalId: (int) $photo->animal_id,
            type: 'photo',
            happenedAt: $photo->taken_at ?? $photo->created_at,
            sourceTable: 'photos',
            sourceId: (int) $photo->id,
            payload: [
                'path' => $photo->path,
                'mime_type' => $photo->mime_type,
                'size_kb' => $photo->size_kb,
                'notes' => $photo->notes,
            ],
        );
    }

    public function removePhoto(Photo|int $photo): void
    {
        $this->deleteFromSource('photos', $photo instanceof Photo ? (int) $photo->id : (int) $photo);
    }

    public function projectOfferRow(array $offerRow): ?AnimalEvent
    {
        $animalId = (int) Arr::get($offerRow, 'animal_id', 0);
        $sourceId = (int) Arr::get($offerRow, 'id', 0);
        if ($animalId <= 0 || $sourceId <= 0) {
            return null;
        }

        return $this->upsertFromSource(
            animalId: $animalId,
            type: 'offer',
            happenedAt: Arr::get($offerRow, 'offered_at') ?? Arr::get($offerRow, 'created_at') ?? Arr::get($offerRow, 'updated_at'),
            sourceTable: 'animal_offers',
            sourceId: $sourceId,
            payload: [
                'status' => Arr::get($offerRow, 'status'),
                'price' => Arr::get($offerRow, 'price'),
                'currency' => Arr::get($offerRow, 'currency'),
                'title' => Arr::get($offerRow, 'title'),
                'notes' => Arr::get($offerRow, 'notes'),
            ],
        );
    }

    public function removeOfferById(int $offerId): void
    {
        $this->deleteFromSource('animal_offers', $offerId);
    }

    public function projectOffer(AnimalOffer $offer): AnimalEvent
    {
        return $this->upsertFromSource(
            animalId: (int) $offer->animal_id,
            type: 'offer',
            happenedAt: $offer->sold_date ?? $offer->updated_at ?? $offer->created_at,
            sourceTable: 'animal_offers',
            sourceId: (int) $offer->id,
            payload: [
                'price' => $offer->price,
                'sold_date' => optional($offer->sold_date)->toDateString(),
                'status' => $offer->sold_date ? 'sold' : 'available',
            ],
        );
    }

    public function removeOffer(AnimalOffer|int $offer): void
    {
        $this->deleteFromSource('animal_offers', $offer instanceof AnimalOffer ? (int) $offer->id : (int) $offer);
    }

    public function rebuildForAnimal(Animal $animal): void
    {
        DB::transaction(function () use ($animal): void {
            AnimalEvent::query()->where('animal_id', $animal->id)->delete();

            foreach ($animal->feedings()->with('feed')->get() as $feeding) {
                $this->projectFeeding($feeding);
            }

            foreach ($animal->weights()->get() as $weight) {
                $this->projectWeight($weight);
            }

            foreach ($animal->sheds()->get() as $shed) {
                $this->projectShed($shed);
            }

            foreach ($animal->animalGenotypes()->with('genotypeCategory')->get() as $genotype) {
                $this->projectGenotype($genotype);
            }

            foreach ($animal->photos()->get() as $photo) {
                $this->projectPhoto($photo);
            }

            if (Schema::hasTable('animal_offers')) {
                foreach ($animal->offers()->get() as $offer) {
                    $this->projectOffer($offer);
                }
            }
        });
    }

    protected function upsertFromSource(
        int $animalId,
        string $type,
        mixed $happenedAt,
        string $sourceTable,
        int $sourceId,
        array $payload,
    ): AnimalEvent {
        $event = $this->findEventBySource($sourceTable, $sourceId);

        $data = [
            'animal_id' => $animalId,
            'type' => $type,
            'happened_at' => $this->normalizeHappenedAt($happenedAt),
            'payload' => array_filter([
                ...$payload,
                'source_table' => $sourceTable,
                'source_id' => $sourceId,
            ], static fn (mixed $value): bool => $value !== null),
        ];

        if ($event) {
            $event->update($data);
            return $event->refresh();
        }

        return AnimalEvent::query()->create($data);
    }

    protected function deleteFromSource(string $sourceTable, int $sourceId): void
    {
        if ($sourceId <= 0) {
            return;
        }

        AnimalEvent::query()
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.source_table')) = ?", [$sourceTable])
            ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.source_id')) AS UNSIGNED) = ?", [$sourceId])
            ->delete();
    }

    protected function findEventBySource(string $sourceTable, int $sourceId): ?AnimalEvent
    {
        return AnimalEvent::query()
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.source_table')) = ?", [$sourceTable])
            ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.source_id')) AS UNSIGNED) = ?", [$sourceId])
            ->first();
    }

    protected function normalizeHappenedAt(mixed $happenedAt): ?string
    {
        if ($happenedAt === null || $happenedAt === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $happenedAt)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }
}
