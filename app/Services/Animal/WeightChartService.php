<?php

namespace App\Services\Animal;

use App\Models\Animal;

class WeightChartService
{
    public function buildDataset(Animal $animal): array
    {
        $weights = $animal->weights()
            ->orderBy('measured_at')
            ->get(['measured_at', 'weight_grams']);

        $feedings = $animal->feedings()
            ->with('feed')
            ->orderBy('fed_at')
            ->get(['feedings.id', 'feedings.fed_at', 'feedings.prey_weight_grams', 'feedings.quantity', 'feedings.feed_id']);

        $weightPoints = [];
        foreach ($weights as $weight) {
            $weightPoints[] = [
                'x' => optional($weight->measured_at)->format('Y-m-d'),
                'y' => $weight->weight_grams !== null ? (float) $weight->weight_grams : null,
            ];
        }

        $feedingPoints = [];
        foreach ($feedings as $feeding) {
            $derivedSize = $feeding->prey_weight_grams !== null
                ? (float) $feeding->prey_weight_grams
                : (($feeding->feed?->amount !== null ? (float) $feeding->feed->amount : null));

            if ($derivedSize === null) {
                continue;
            }

            $feedingPoints[] = [
                'x' => optional($feeding->fed_at)->format('Y-m-d'),
                'y' => $derivedSize,
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Waga (g)',
                    'borderColor' => '#4fd1c5',
                    'backgroundColor' => 'rgba(79, 209, 197, 0.2)',
                    'tension' => 0.25,
                    'data' => $weightPoints,
                ],
                [
                    'label' => 'Wielkosc karmy (g)',
                    'borderColor' => '#f6ad55',
                    'backgroundColor' => 'rgba(246, 173, 85, 0.2)',
                    'stepped' => true,
                    'data' => $feedingPoints,
                ],
            ],
        ];
    }
}
