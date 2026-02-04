<?php

namespace App\Services\Animal;

use App\Models\Animal;
use App\Models\AnimalOffer;
use App\Models\Feeding;
use App\Models\Shed;
use App\Services\Animal\DTO\AnimalProfileDTO;
use Illuminate\Support\Facades\Schema;

class AnimalProfileQueryService
{
    public function __construct(
        protected TimelineQueryService $timelineQueryService,
        protected WeightChartService $weightChartService,
    ) {
    }

    public function build(Animal $animal, int $shedsPage = 1, int $shedsPerPage = 10): AnimalProfileDTO
    {
        $animal->loadMissing(['species', 'animalGenotypes.genotypeCategory', 'photos', 'coverPhoto']);

        $feedings = Feeding::query()
            ->where('animal_id', $animal->id)
            ->with('feed')
            ->orderByDesc('fed_at')
            ->get();

        $feedingsByYear = [];
        foreach ($feedings as $feeding) {
            $year = optional($feeding->fed_at)->format('Y') ?? 'Brak daty';
            $feedingsByYear[$year] ??= [];
            $feedingsByYear[$year][] = $feeding;
        }

        $sheds = Shed::query()
            ->where('animal_id', $animal->id)
            ->orderByDesc('shed_at')
            ->paginate($shedsPerPage, ['*'], 'shedsPage', $shedsPage);

        $genotypeChips = $animal->animalGenotypes
            ->map(fn ($item): array => [
                'id' => $item->id,
                'name' => $this->normalizeUtf8((string) ($item->genotypeCategory?->name ?? 'Gen')),
                'code' => $this->normalizeUtf8((string) ($item->genotypeCategory?->gene_code ?? '')),
                'type' => (string) $item->type,
            ])->values()->all();

        $coverPhoto = $animal->coverPhoto ?? $animal->photos->sortByDesc('id')->first();

        return new AnimalProfileDTO(
            identity: [
                'id' => $animal->id,
                'name' => $this->normalizeUtf8((string) $animal->name),
                'species' => $this->normalizeUtf8((string) ($animal->species?->name ?? '')),
                'sex' => $animal->sex,
                'hatch_date' => optional($animal->hatch_date)->toDateString(),
                'acquired_at' => optional($animal->acquired_at)->toDateString(),
                'feeding_interval_days' => $animal->feeding_interval_days,
                'current_weight_grams' => $animal->current_weight_grams,
                'secret_tag' => $this->normalizeUtf8((string) ($animal->secret_tag ?? '')),
                'notes' => $this->normalizeUtf8((string) ($animal->notes ?? '')),
                'cover_photo_url' => $coverPhoto?->url,
            ],
            genotypeChips: $genotypeChips,
            feedingsByYear: $feedingsByYear,
            sheds: $sheds,
            offerStatus: $this->resolveOfferStatus($animal),
            weightChartDataset: $this->weightChartService->buildDataset($animal),
            timelineByMonth: $this->timelineQueryService->timelineGroupedByMonth($animal),
        );
    }

    protected function resolveOfferStatus(Animal $animal): ?array
    {
        if (! Schema::hasTable('animal_offers')) {
            return null;
        }

        $offer = AnimalOffer::query()
            ->where('animal_id', $animal->id)
            ->orderByDesc('id')
            ->first();

        if (! $offer) {
            return null;
        }

        return [
            'id' => $offer->id,
            'status' => $offer->sold_date ? 'sold' : 'available',
            'price' => $offer->price,
            'sold_date' => optional($offer->sold_date)->toDateString(),
            'updated_at' => optional($offer->updated_at)?->toDateTimeString(),
        ];
    }

    protected function normalizeUtf8(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        if (function_exists('mb_check_encoding') && mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }

        return preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $value) ?: '';
    }
}
