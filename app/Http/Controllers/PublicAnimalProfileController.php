<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Weight;
use App\Services\Animal\AnimalProfileQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PublicAnimalProfileController extends Controller
{
    public function __invoke(string $token, Request $request, AnimalProfileQueryService $queryService)
    {
        $animal = Animal::query()
            ->where('public_profile_enabled', true)
            ->where('public_profile_token', $token)
            ->with(['species', 'photos', 'coverPhoto', 'animalGenotypes.genotypeCategory'])
            ->firstOrFail();

        $profile = $queryService->build(
            animal: $animal,
            shedsPage: max(1, (int) $request->query('shedsPage', 1)),
            shedsPerPage: 10,
        );

        $weights = Weight::query()
            ->where('animal_id', $animal->id)
            ->orderByDesc('measured_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'weightsPage');

        return view('animals.public-show', [
            'animal' => $animal,
            'profile' => $profile,
            'genotypeSummary' => $this->genotypeSummary($profile->genotypeChips),
            'weights' => $weights,
            'chart' => $this->buildChart($profile->weightChartDataset),
        ]);
    }

    protected function genotypeSummary(array $genotypeChips): string
    {
        if ($genotypeChips === []) {
            return 'Brak wpisanej genetyki';
        }

        $chips = collect($genotypeChips);

        $formatNames = static fn (string $type): array => $chips
            ->filter(static fn (array $chip): bool => strtolower((string) ($chip['type'] ?? '')) === $type)
            ->pluck('name')
            ->filter(static fn (mixed $name): bool => is_string($name) && trim($name) !== '')
            ->map(static fn (string $name): string => trim($name))
            ->unique()
            ->values()
            ->all();

        $v = $formatNames('v');
        $h = $formatNames('h');
        $p = $formatNames('p');

        $parts = [];
        if ($v !== []) {
            $parts[] = implode(', ', $v);
        }
        if ($h !== []) {
            $parts[] = 'het. '.implode(', ', $h);
        }
        if ($p !== []) {
            $parts[] = 'poss het '.implode(', ', $p);
        }

        return $parts !== [] ? implode(' | ', $parts) : 'Brak wpisanej genetyki';
    }

    protected function buildChart(array $dataset): array
    {
        $weightPoints = $this->normalizePoints(collect($dataset['datasets'][0]['data'] ?? []));
        if ($weightPoints->isEmpty()) {
            return [
                'has_data' => false,
            ];
        }

        $dates = $weightPoints->pluck('x')->unique()->sort()->values();
        $yValues = $weightPoints->pluck('y');
        $minRaw = (float) $yValues->min();
        $maxRaw = (float) $yValues->max();

        $range = max($maxRaw - $minRaw, 1.0);
        $padding = max(5.0, $range * 0.1);
        $minY = max(0.0, $minRaw - $padding);
        $maxY = $maxRaw + $padding;

        $step = $this->resolveTickStep($maxY - $minY, 6);
        $minY = floor($minY / $step) * $step;
        $maxY = ceil($maxY / $step) * $step;

        if (($maxY - $minY) < $step) {
            $maxY = $minY + $step;
        }

        $dateIndex = [];
        foreach ($dates as $index => $date) {
            $dateIndex[(string) $date] = $index;
        }

        $chartBounds = [
            'left' => 12.0,
            'right' => 98.0,
            'top' => 2.0,
            'bottom' => 45.0,
        ];

        $linePoints = $this->toChartPoints($weightPoints, $dateIndex, $minY, $maxY, $chartBounds);

        return [
            'has_data' => true,
            'line' => $this->buildLinePath($linePoints),
            'area' => $this->buildAreaPath($linePoints, $chartBounds['bottom']),
            'points' => $linePoints,
            'y_ticks' => $this->buildYTicks($minY, $maxY, $chartBounds),
            'x_ticks' => $this->buildXTicks($dates, $dateIndex, $chartBounds),
            'bounds' => $chartBounds,
        ];
    }

    protected function buildYTicks(float $minY, float $maxY, array $bounds): array
    {
        $ticks = [];
        $step = $this->resolveTickStep($maxY - $minY, 6);

        for ($value = $minY; $value <= $maxY + 0.0001; $value += $step) {
            $ratio = ($value - $minY) / ($maxY - $minY);
            $y = $bounds['bottom'] - ($ratio * ($bounds['bottom'] - $bounds['top']));

            $ticks[] = [
                'y' => $y,
                'label' => number_format($value, $step < 1 ? 1 : 0, '.', ''),
            ];
        }

        return $ticks;
    }

    protected function buildXTicks(Collection $dates, array $dateIndex, array $bounds): array
    {
        $count = $dates->count();
        if ($count === 0) {
            return [];
        }

        $maxLabels = 6;
        $step = (int) max(1, ceil($count / $maxLabels));
        $maxIndex = max(count($dateIndex) - 1, 1);
        $width = $bounds['right'] - $bounds['left'];

        $ticks = [];
        for ($i = 0; $i < $count; $i += $step) {
            $date = (string) $dates[$i];
            $index = $dateIndex[$date] ?? null;
            if ($index === null) {
                continue;
            }

            $x = $bounds['left'] + (($index / $maxIndex) * $width);
            $ticks[] = [
                'x' => $x,
                'label' => $this->formatDateTickLabel($date),
                'raw' => $date,
            ];
        }

        $last = (string) $dates->last();
        if (($ticks[count($ticks) - 1]['raw'] ?? null) !== $last) {
            $ticks[] = [
                'x' => $bounds['right'],
                'label' => $this->formatDateTickLabel($last),
                'raw' => $last,
            ];
        }

        return array_map(static fn (array $tick): array => [
            'x' => $tick['x'],
            'label' => $tick['label'],
        ], $ticks);
    }

    protected function formatDateTickLabel(string $date): string
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if (! $parsed instanceof \DateTimeImmutable) {
            return $date;
        }

        return $parsed->format('d.m.y');
    }

    protected function resolveTickStep(float $range, int $targetTicks): float
    {
        $targetTicks = max(2, $targetTicks);
        $range = max($range, 0.000001);
        $roughStep = $range / ($targetTicks - 1);
        $magnitude = 10 ** floor(log10($roughStep));
        $normalized = $roughStep / $magnitude;

        if ($normalized <= 1.0) {
            $nice = 1.0;
        } elseif ($normalized <= 2.0) {
            $nice = 2.0;
        } elseif ($normalized <= 2.5) {
            $nice = 2.5;
        } elseif ($normalized <= 5.0) {
            $nice = 5.0;
        } else {
            $nice = 10.0;
        }

        return $nice * $magnitude;
    }

    protected function normalizePoints(Collection $points): Collection
    {
        return $points
            ->filter(static function (mixed $point): bool {
                return is_array($point)
                    && isset($point['x'], $point['y'])
                    && is_string($point['x'])
                    && is_numeric($point['y']);
            })
            ->map(static fn (array $point): array => [
                'x' => trim((string) $point['x']),
                'y' => (float) $point['y'],
            ])
            ->sortBy('x')
            ->values();
    }

    protected function toChartPoints(
        Collection $points,
        array $dateIndex,
        float $minY,
        float $maxY,
        array $bounds,
    ): array {
        $coordinates = [];
        $maxIndex = max(count($dateIndex) - 1, 1);
        $width = $bounds['right'] - $bounds['left'];
        $height = $bounds['bottom'] - $bounds['top'];

        foreach ($points as $point) {
            $index = $dateIndex[$point['x']] ?? null;
            if ($index === null) {
                continue;
            }

            $x = $bounds['left'] + (($index / $maxIndex) * $width);
            $ratio = ($point['y'] - $minY) / ($maxY - $minY);
            $y = $bounds['bottom'] - ($ratio * $height);
            $coordinates[] = ['x' => $x, 'y' => $y];
        }

        return $coordinates;
    }

    protected function buildLinePath(array $points): string
    {
        if ($points === []) {
            return '';
        }

        $path = [];
        foreach ($points as $i => $point) {
            $command = $i === 0 ? 'M' : 'L';
            $path[] = sprintf('%s %.2F %.2F', $command, $point['x'], $point['y']);
        }

        return implode(' ', $path);
    }

    protected function buildAreaPath(array $points, float $baselineY): string
    {
        if ($points === []) {
            return '';
        }

        $first = $points[0];
        $last = $points[count($points) - 1];

        return trim(sprintf(
            '%s L %.2F %.2F L %.2F %.2F Z',
            $this->buildLinePath($points),
            $last['x'],
            $baselineY,
            $first['x'],
            $baselineY
        ));
    }
}
