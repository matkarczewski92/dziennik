<?php

namespace App\Services\Animal;

use App\Models\Animal;
use App\Models\AnimalEvent;
use Carbon\Carbon;

class TimelineQueryService
{
    public function timelineGroupedByMonth(Animal $animal): array
    {
        $events = AnimalEvent::query()
            ->where('animal_id', $animal->id)
            ->orderByDesc('happened_at')
            ->orderByDesc('id')
            ->get();

        $groups = [];
        foreach ($events as $event) {
            $date = $event->happened_at ?? $event->created_at;
            if (! $date) {
                continue;
            }

            $monthKey = $date->format('Y-m');
            $monthLabel = Carbon::parse($date)->locale('pl')->translatedFormat('F Y');

            if (! isset($groups[$monthKey])) {
                $groups[$monthKey] = [
                    'key' => $monthKey,
                    'label' => $monthLabel,
                    'events' => [],
                ];
            }

            $groups[$monthKey]['events'][] = [
                'id' => $event->id,
                'type' => $event->type,
                'date' => Carbon::parse($date)->format('Y-m-d'),
                'title' => $this->buildEventTitle($event),
                'payload' => $event->payload ?? [],
            ];
        }

        return array_values($groups);
    }

    protected function buildEventTitle(AnimalEvent $event): string
    {
        $payload = $event->payload ?? [];

        return match ($event->type) {
            'feeding' => sprintf(
                'Karmienie: %s x%s',
                (string) ($payload['feed_name'] ?? $payload['prey'] ?? 'pokarm'),
                (string) ($payload['quantity'] ?? 1),
            ),
            'weight' => sprintf('Wazenie: %s g', (string) ($payload['value'] ?? '?')),
            'shed' => 'Wylinka',
            'photo' => 'Dodano zdjecie',
            'genotype' => sprintf('Genotyp: %s (%s)', (string) ($payload['gene_name'] ?? 'gen'), (string) ($payload['type'] ?? 'h')),
            'offer' => sprintf('Oferta: %s', (string) ($payload['status'] ?? 'status nieznany')),
            default => ucfirst($event->type),
        };
    }
}
