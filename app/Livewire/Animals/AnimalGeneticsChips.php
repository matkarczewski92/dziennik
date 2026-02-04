<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use App\Models\AnimalGenotype;
use App\Models\AnimalGenotypeCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Component;

class AnimalGeneticsChips extends Component
{
    use AuthorizesRequests;

    public int $animalId;

    public array $form = [];

    public ?int $editingId = null;

    public function mount(int $animalId): void
    {
        $this->animalId = $animalId;
        $this->resetForm();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'form.gene_name' => ['required', 'string', 'max:255'],
            'form.type' => ['required', 'in:v,h,p'],
        ]);

        $animal = $this->animal();
        if ($animal->imported_from_api) {
            session()->flash('error', 'Genetyka zwierzat z API jest tylko do odczytu.');
            return;
        }
        $this->authorize('update', $animal);

        $geneName = trim((string) $validated['form']['gene_name']);
        $category = AnimalGenotypeCategory::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($geneName)])
            ->first();

        if (! $category) {
            $this->addError('form.gene_name', 'Nie znaleziono takiego genu w slowniku.');

            return;
        }

        if ($this->editingId) {
            $genotype = AnimalGenotype::query()
                ->where('animal_id', $animal->id)
                ->findOrFail($this->editingId);

            $genotype->update([
                'genotype_id' => (int) $category->id,
                'type' => (string) $validated['form']['type'],
            ]);

            session()->flash('success', 'Genetyka zostala zaktualizowana.');
        } else {
            AnimalGenotype::query()->updateOrCreate(
                [
                    'animal_id' => $animal->id,
                    'genotype_id' => (int) $category->id,
                ],
                [
                    'type' => (string) $validated['form']['type'],
                ],
            );

            session()->flash('success', 'Genetyka zostala dodana.');
        }

        $this->resetForm();
        $this->dispatch('animal-profile-refresh');
    }

    public function startEdit(int $genotypeId): void
    {
        if ($this->animal()->imported_from_api) {
            return;
        }

        $genotype = AnimalGenotype::query()
            ->where('animal_id', $this->animal()->id)
            ->with('genotypeCategory')
            ->findOrFail($genotypeId);

        $this->editingId = $genotype->id;
        $this->form = [
            'gene_name' => (string) $genotype->genotypeCategory?->name,
            'type' => (string) $genotype->type,
        ];
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function delete(int $genotypeId): void
    {
        if ($this->animal()->imported_from_api) {
            return;
        }

        $genotype = AnimalGenotype::query()
            ->where('animal_id', $this->animal()->id)
            ->findOrFail($genotypeId);

        $genotype->delete();
        if ($this->editingId === $genotypeId) {
            $this->resetForm();
        }

        session()->flash('success', 'Genetyka zostala usunieta.');
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
            'gene_name' => '',
            'type' => 'h',
        ];
        $this->resetValidation();
    }

    public function render()
    {
        $animal = $this->animal();

        return view('livewire.animals.animal-genetics-chips', [
            'genotypes' => AnimalGenotype::query()
                ->where('animal_id', $animal->id)
                ->with('genotypeCategory')
                ->orderByRaw("CASE type WHEN 'v' THEN 1 WHEN 'h' THEN 2 WHEN 'p' THEN 3 ELSE 4 END")
                ->orderByRaw('LOWER((SELECT name FROM animal_genotype_category WHERE animal_genotype_category.id = animal_genotype.genotype_id))')
                ->get(),
            'dictionary' => AnimalGenotypeCategory::query()->orderBy('name')->get(['id', 'name', 'gene_code']),
            'isReadOnly' => (bool) $animal->imported_from_api,
        ]);
    }
}
