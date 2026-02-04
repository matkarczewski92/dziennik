<?php

namespace App\Services\Animal;

use App\Models\Animal;
use App\Models\Feeding;
use App\Models\Shed;
use App\Services\Animal\DTO\AnimalProfileDTO;
use Illuminate\Support\Facades\DB;
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
        $animal->loadMissing(['species', 'animalGenotypes.genotypeCategory']);

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
                'name' => (string) ($item->genotypeCategory?->name ?? 'Gen'),
                'code' => (string) ($item->genotypeCategory?->gene_code ?? ''),
                'type' => (string) $item->type,
            ])->values()->all();

        return new AnimalProfileDTO(
            identity: [
                'id' => $animal->id,
                'name' => $animal->name,
                'species' => $animal->species?->name,
                'sex' => $animal->sex,
                'hatch_date' => optional($animal->hatch_date)->toDateString(),
                'acquired_at' => optional($animal->acquired_at)->toDateString(),
                'feeding_interval_days' => $animal->feeding_interval_days,
                'current_weight_grams' => $animal->current_weight_grams,
                'secret_tag' => $animal->secret_tag,
                'notes' => $animal->notes,
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

        $offer = DB::table('animal_offers')
            ->where('animal_id', $animal->id)
            ->orderByDesc('id')
            ->first();

        if (! $offer) {
            return null;
        }

        return [
            'id' => $offer->id ?? null,
            'status' => $offer->status ?? null,
            'price' => $offer->price ?? null,
            'currency' => $offer->currency ?? null,
            'title' => $offer->title ?? null,
            'updated_at' => $offer->updated_at ?? null,
        ];
    }
}
