<?php

namespace App\Livewire\Animals;

use App\Exceptions\HodowlaApiException;
use App\Models\Animal;
use App\Models\AnimalSpecies;
use App\Services\AnimalImportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public bool $showAnimalModal = false;

    public bool $showDeleteModal = false;

    public bool $showImportModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $name = '';

    public ?int $species_id = null;

    public ?string $morph = null;

    public string $sex = 'unknown';

    public ?string $hatch_date = null;

    public ?string $acquired_at = null;

    public ?float $current_weight_grams = null;

    public int $feeding_interval_days = 14;

    public ?string $notes = null;

    public string $importSecretTag = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'species_id' => ['nullable', 'integer', 'exists:animal_species,id'],
            'morph' => ['nullable', 'string', 'max:255'],
            'sex' => ['required', 'in:male,female,unknown'],
            'hatch_date' => ['nullable', 'date'],
            'acquired_at' => ['nullable', 'date'],
            'current_weight_grams' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'feeding_interval_days' => ['required', 'integer', 'min:1', 'max:90'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function animals()
    {
        return Animal::query()
            ->with('species')
            ->ownedBy(auth()->id())
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($q): void {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('morph', 'like', '%'.$this->search.'%')
                        ->orWhere('secret_tag', 'like', '%'.$this->search.'%')
                        ->orWhereHas('species', function ($speciesQuery): void {
                            $speciesQuery->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->latest()
            ->paginate(12);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showAnimalModal = true;
    }

    public function openEditModal(int $id): void
    {
        $animal = Animal::query()->ownedBy(auth()->id())->findOrFail($id);
        $this->authorize('update', $animal);

        $this->editingId = $animal->id;
        $this->name = $animal->name;
        $this->species_id = $animal->species_id;
        $this->morph = $animal->morph;
        $this->sex = $animal->sex;
        $this->hatch_date = $animal->hatch_date?->toDateString();
        $this->acquired_at = $animal->acquired_at?->toDateString();
        $this->current_weight_grams = $animal->current_weight_grams ? (float) $animal->current_weight_grams : null;
        $this->feeding_interval_days = (int) $animal->feeding_interval_days;
        $this->notes = $animal->notes;
        $this->showAnimalModal = true;
    }

    public function saveAnimal(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            $animal = Animal::query()->ownedBy(auth()->id())->findOrFail($this->editingId);
            $this->authorize('update', $animal);
            $animal->update($validated);
            session()->flash('success', 'Dane zwierzecia zostaly zaktualizowane.');
        } else {
            $this->authorize('create', Animal::class);
            $animal = Animal::query()->create([
                ...$validated,
                'user_id' => auth()->id(),
            ]);
            session()->flash('success', 'Dodano nowe zwierze.');
        }

        $this->showAnimalModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $animal = Animal::query()->ownedBy(auth()->id())->findOrFail($id);
        $this->authorize('delete', $animal);

        $this->deletingId = $animal->id;
        $this->showDeleteModal = true;
    }

    public function deleteAnimal(): void
    {
        if (! $this->deletingId) {
            return;
        }

        $animal = Animal::query()->ownedBy(auth()->id())->findOrFail($this->deletingId);
        $this->authorize('delete', $animal);
        $animal->delete();

        $this->deletingId = null;
        $this->showDeleteModal = false;
        session()->flash('success', 'Zwierze zostalo usuniete.');
    }

    public function openImportModal(): void
    {
        $this->importSecretTag = '';
        $this->showImportModal = true;
    }

    public function importAnimal(AnimalImportService $importService): void
    {
        $this->validate([
            'importSecretTag' => ['required', 'string', 'max:255'],
        ]);

        try {
            $animal = $importService->importBySecretTag(auth()->user(), trim($this->importSecretTag));
            $this->showImportModal = false;
            session()->flash('success', "Import zakonczony: {$animal->name}");
        } catch (HodowlaApiException $exception) {
            $this->addError('importSecretTag', $exception->getMessage());
        }
    }

    public function resetForm(): void
    {
        $this->resetValidation();
        $this->name = '';
        $this->species_id = null;
        $this->morph = null;
        $this->sex = 'unknown';
        $this->hatch_date = null;
        $this->acquired_at = null;
        $this->current_weight_grams = null;
        $this->feeding_interval_days = 14;
        $this->notes = null;
    }

    public function render()
    {
        return view('livewire.animals.index', [
            'animals' => $this->animals,
            'speciesOptions' => AnimalSpecies::query()->orderBy('name')->get(),
        ]);
    }
}
