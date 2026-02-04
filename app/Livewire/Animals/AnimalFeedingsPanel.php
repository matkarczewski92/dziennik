<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use App\Models\Feed;
use App\Models\Feeding;
use App\Services\FeedingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class AnimalFeedingsPanel extends Component
{
    use AuthorizesRequests;

    public int $animalId;

    public array $form = [];

    public ?int $editingId = null;

    public array $expandedYears = [];

    public function mount(int $animalId): void
    {
        $this->animalId = $animalId;
        $this->resetForm();
        $this->expandedYears[(string) now()->year] = true;
    }

    public function save(FeedingService $feedingService): void
    {
        $data = $this->validate([
            'form.fed_at' => ['required', 'date'],
            'form.feed_id' => ['required', 'integer', 'exists:feeds,id'],
            'form.quantity' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $payload = [
            ...$data['form'],
            'prey_weight_grams' => null,
            'notes' => null,
        ];

        $animal = $this->animal();

        if ($this->editingId) {
            $feeding = Feeding::query()
                ->ownedBy(auth()->id())
                ->where('animal_id', $animal->id)
                ->findOrFail($this->editingId);

            $this->authorize('update', $feeding);
            $feedingService->update(auth()->user(), $feeding, $payload);
            session()->flash('success', 'Karmienie zostalo zaktualizowane.');
        } else {
            $this->authorize('update', $animal);
            $feedingService->create(auth()->user(), $animal, $payload);
            session()->flash('success', 'Karmienie zostalo dodane.');
        }

        $this->resetForm();
        $this->dispatch('animal-profile-refresh');
    }

    public function startEdit(int $feedingId): void
    {
        $feeding = Feeding::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal()->id)
            ->findOrFail($feedingId);

        $this->editingId = $feeding->id;
        $this->form = [
            'fed_at' => $feeding->fed_at?->toDateString(),
            'feed_id' => $feeding->feed_id,
            'quantity' => (int) $feeding->quantity,
        ];

        $year = $feeding->fed_at?->format('Y') ?? 'Brak daty';
        $this->expandedYears[$year] = true;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function delete(int $feedingId, FeedingService $feedingService): void
    {
        $feeding = Feeding::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal()->id)
            ->findOrFail($feedingId);

        $this->authorize('delete', $feeding);
        $feedingService->delete(auth()->user(), $feeding);

        if ($this->editingId === $feedingId) {
            $this->resetForm();
        }

        session()->flash('success', 'Karmienie zostalo usuniete.');
        $this->dispatch('animal-profile-refresh');
    }

    public function toggleYear(string $year): void
    {
        $current = (bool) ($this->expandedYears[$year] ?? false);
        $this->expandedYears[$year] = ! $current;
    }

    protected function animal(): Animal
    {
        return Animal::query()
            ->ownedBy(auth()->id())
            ->findOrFail($this->animalId);
    }

    protected function resetForm(): void
    {
        $defaultFeedId = Feeding::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animalId)
            ->latest('fed_at')
            ->value('feed_id');

        if (! $defaultFeedId) {
            $defaultFeedId = Feed::query()->orderBy('id')->value('id');
        }

        $this->editingId = null;
        $this->form = [
            'fed_at' => now()->toDateString(),
            'feed_id' => $defaultFeedId ? (int) $defaultFeedId : null,
            'quantity' => 1,
        ];

        $this->resetValidation();
    }

    public function render()
    {
        $feedings = Feeding::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal()->id)
            ->with('feed')
            ->orderByDesc('fed_at')
            ->orderByDesc('id')
            ->get();

        $grouped = $feedings->groupBy(static function (Feeding $feeding): string {
            return $feeding->fed_at?->format('Y') ?? 'Brak daty';
        });

        return view('livewire.animals.animal-feedings-panel', [
            'feedingsByYear' => $grouped,
            'feedOptions' => Feed::query()->orderBy('id')->get(['id', 'name']),
        ]);
    }
}
