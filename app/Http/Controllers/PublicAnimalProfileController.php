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

        $minY = floor($minRaw / 50) * 50;
        $maxY = ceil($maxRaw / 50) * 50;
        $minY = min($minY, floor(($minRaw - 10) / 10) * 10);
        $maxY = max($maxY, ceil(($maxRaw + 10) / 10) * 10);

        if (abs($maxY - $minY) < 0.001) {
            $minY -= 10;
            $maxY += 10;
        }

        $dateIndex = [];
        foreach ($dates as $index => $date) {
            $dateIndex[(string) $date] = $index;
        }

        $chartBounds = [
            'left' => 11.0,
            'right' => 100.0,
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
        $step = ($maxY - $minY) > 350 ? 100.0 : 50.0;

        for ($value = $minY; $value <= $maxY + 0.0001; $value += $step) {
            $ratio = ($value - $minY) / ($maxY - $minY);
            $y = $bounds['bottom'] - ($ratio * ($bounds['bottom'] - $bounds['top']));

            $ticks[] = [
                'y' => $y,
                'label' => number_format($value, 0, '.', ''),
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

        $maxLabels = 8;
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
                'label' => $date,
            ];
        }

        $last = (string) $dates->last();
        if (($ticks[count($ticks) - 1]['label'] ?? null) !== $last) {
            $ticks[] = [
                'x' => $bounds['right'],
                'label' => $last,
            ];
        }

        return $ticks;
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
