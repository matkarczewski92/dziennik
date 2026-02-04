<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use App\Models\AnimalSpecies;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class AnimalHero extends Component
{
    use AuthorizesRequests;

    public int $animalId;

    public array $identity = [];

    public array $genotypeChips = [];

    public bool $showEditModal = false;

    public array $form = [];

    public function genotypeSummary(): string
    {
        if ($this->genotypeChips === []) {
            return 'Brak wpisanej genetyki';
        }

        $chips = collect($this->genotypeChips);

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

    public function openEditModal(): void
    {
        $animal = $this->animal();
        $this->authorize('update', $animal);

        $this->form = [
            'name' => (string) $animal->name,
            'species_id' => $animal->species_id,
            'sex' => (string) $animal->sex,
            'hatch_date' => $animal->hatch_date?->toDateString(),
            'acquired_at' => $animal->acquired_at?->toDateString(),
            'current_weight_grams' => $animal->current_weight_grams !== null ? (float) $animal->current_weight_grams : null,
            'feeding_interval_days' => (int) $animal->feeding_interval_days,
        ];

        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->resetValidation();
    }

    public function saveBasicData(): void
    {
        $animal = $this->animal();
        $this->authorize('update', $animal);

        $validated = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.species_id' => ['nullable', 'integer', 'exists:animal_species,id'],
            'form.sex' => ['required', 'in:male,female,unknown'],
            'form.hatch_date' => ['nullable', 'date'],
            'form.acquired_at' => ['nullable', 'date'],
            'form.current_weight_grams' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'form.feeding_interval_days' => ['required', 'integer', 'min:1', 'max:90'],
        ]);

        $animal->update($validated['form']);
        $this->showEditModal = false;

        session()->flash('success', 'Dane podstawowe zostaly zaktualizowane.');
        $this->dispatch('animal-profile-refresh');
    }

    protected function animal(): Animal
    {
        return Animal::query()
            ->ownedBy(auth()->id())
            ->findOrFail($this->animalId);
    }

    public function render()
    {
        return view('livewire.animals.animal-hero', [
            'speciesOptions' => AnimalSpecies::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
