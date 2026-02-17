<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use App\Models\Weight;
use App\Services\Animal\WeightChartService;
use App\Services\WeightService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class AnimalWeightsChart extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public int $animalId;

    public array $form = [];

    public ?int $editingId = null;

    public function mount(int $animalId): void
    {
        $this->animalId = $animalId;
        $this->resetForm();
    }

    public function save(WeightService $weightService): void
    {
        $data = $this->validate([
            'form.measured_at' => ['required', 'date'],
            'form.weight_grams' => ['required', 'numeric', 'min:0', 'max:99999.99'],
        ]);
        $payload = [
            ...$data['form'],
            'notes' => null,
        ];

        $animal = $this->animal();

        if ($this->editingId) {
            $weight = Weight::query()
                ->ownedBy(auth()->id())
                ->where('animal_id', $animal->id)
                ->findOrFail($this->editingId);

            $this->authorize('update', $weight);
            $weightService->update(auth()->user(), $weight, $payload);
            session()->flash('success', 'Wazenie zostalo zaktualizowane.');
        } else {
            $this->authorize('update', $animal);
            $weightService->create(auth()->user(), $animal, $payload);
            session()->flash('success', 'Wazenie zostalo dodane.');
        }

        $this->resetForm();
        $this->dispatch('animal-profile-refresh');
    }

    public function startEdit(int $weightId): void
    {
        $weight = Weight::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal()->id)
            ->findOrFail($weightId);

        $this->editingId = $weight->id;
        $this->form = [
            'measured_at' => $weight->measured_at?->toDateString(),
            'weight_grams' => $weight->weight_grams !== null ? (float) $weight->weight_grams : null,
        ];
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function delete(int $weightId, WeightService $weightService): void
    {
        $weight = Weight::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal()->id)
            ->findOrFail($weightId);

        $this->authorize('delete', $weight);
        $weightService->delete(auth()->user(), $weight);

        if ($this->editingId === $weightId) {
            $this->resetForm();
        }

        session()->flash('success', 'Wazenie zostalo usuniete.');
        $this->dispatch('animal-profile-refresh');
    }

    protected function animal(): Animal
    {
        return Animal::query()
            ->ownedBy(auth()->id())
            ->findOrFail($this->animalId);
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'measured_at' => now()->toDateString(),
            'weight_grams' => null,
        ];
        $this->resetValidation();
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
            'first_label' => (string) ($dates->first() ?? ''),
            'last_label' => (string) ($dates->last() ?? ''),
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

    public function render(WeightChartService $chartService)
    {
        $animal = $this->animal();
        $dataset = $chartService->buildDataset($animal);

        return view('livewire.animals.animal-weights-chart', [
            'chart' => $this->buildChart($dataset),
            'weights' => Weight::query()
                ->ownedBy(auth()->id())
                ->where('animal_id', $animal->id)
                ->orderByDesc('measured_at')
                ->orderByDesc('id')
                ->paginate(10, ['*'], 'weightsPanelPage'),
        ]);
    }
}
