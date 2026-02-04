<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use App\Models\Shed;
use App\Services\ShedService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class AnimalShedsWidget extends Component
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

    public function save(ShedService $shedService): void
    {
        $data = $this->validate([
            'form.shed_at' => ['required', 'date'],
            'form.quality' => ['nullable', 'string', 'max:30'],
            'form.notes' => ['nullable', 'string'],
        ]);

        $animal = $this->animal();

        if ($this->editingId) {
            $shed = Shed::query()
                ->ownedBy(auth()->id())
                ->where('animal_id', $animal->id)
                ->findOrFail($this->editingId);

            $this->authorize('update', $shed);
            $shedService->update(auth()->user(), $shed, $data['form']);
            session()->flash('success', 'Wylinka zostala zaktualizowana.');
        } else {
            $this->authorize('update', $animal);
            $shedService->create(auth()->user(), $animal, $data['form']);
            session()->flash('success', 'Wylinka zostala dodana.');
        }

        $this->resetForm();
        $this->dispatch('animal-profile-refresh');
    }

    public function startEdit(int $shedId): void
    {
        $shed = Shed::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal()->id)
            ->findOrFail($shedId);

        $this->editingId = $shed->id;
        $this->form = [
            'shed_at' => $shed->shed_at?->toDateString(),
            'quality' => $shed->quality ?? '',
            'notes' => $shed->notes ?? '',
        ];
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function delete(int $shedId, ShedService $shedService): void
    {
        $shed = Shed::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal()->id)
            ->findOrFail($shedId);

        $this->authorize('delete', $shed);
        $shedService->delete(auth()->user(), $shed);

        if ($this->editingId === $shedId) {
            $this->resetForm();
        }

        session()->flash('success', 'Wylinka zostala usunieta.');
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
            'shed_at' => now()->toDateString(),
            'quality' => '',
            'notes' => '',
        ];
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.animals.animal-sheds-widget', [
            'sheds' => Shed::query()
                ->ownedBy(auth()->id())
                ->where('animal_id', $this->animal()->id)
                ->orderByDesc('shed_at')
                ->orderByDesc('id')
                ->paginate(10, ['*'], 'shedsPage'),
        ]);
    }
}
