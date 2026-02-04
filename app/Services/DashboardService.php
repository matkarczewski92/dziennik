<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\SystemConfig;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class DashboardService
{
    public function globalMessage(): ?string
    {
        return SystemConfig::getValue('global_message');
    }

    public function feedingReminders(User $user): array
    {
        $today = CarbonImmutable::today();
        $tomorrow = $today->addDay();

        $animals = Animal::query()
            ->ownedBy($user)
            ->withMax('feedings', 'fed_at')
            ->orderBy('name')
            ->get();

        $result = ['today' => collect(), 'tomorrow' => collect()];

        foreach ($animals as $animal) {
            $lastFedAt = $animal->feedings_max_fed_at
                ? CarbonImmutable::parse($animal->feedings_max_fed_at)
                : null;

            $nextFeed = $lastFedAt
                ? $lastFedAt->addDays(max(1, (int) $animal->feeding_interval_days))
                : $today;

            $payload = [
                'animal' => $animal,
                'last_fed_at' => $lastFedAt?->toDateString(),
                'next_feed_at' => $nextFeed->toDateString(),
            ];

            if ($nextFeed->equalTo($today)) {
                $result['today']->push($payload);
            } elseif ($nextFeed->equalTo($tomorrow)) {
                $result['tomorrow']->push($payload);
            }
        }

        return [
            'today' => $result['today']->values(),
            'tomorrow' => $result['tomorrow']->values(),
        ];
    }

    public function weightReminders(User $user): Collection
    {
        $today = CarbonImmutable::today();

        return Animal::query()
            ->ownedBy($user)
            ->with('species')
            ->withMax('weights', 'measured_at')
            ->orderBy('name')
            ->get()
            ->filter(function (Animal $animal) use ($today): bool {
                if (! $animal->weights_max_measured_at) {
                    return true;
                }

                $lastMeasured = CarbonImmutable::parse($animal->weights_max_measured_at);

                return $lastMeasured->diffInDays($today) >= 30;
            })
            ->values();
    }

}
