<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use App\Services\Animal\AnimalProfileQueryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class AnimalProfilePage extends Component
{
    use AuthorizesRequests;

    public Animal $animal;

    #[On('animal-profile-refresh')]
    public function refreshProfile(): void
    {
        $this->animal = $this->animal->refresh();
    }

    public function mount(Animal $animal): void
    {
        $this->authorize('view', $animal);
        $this->animal = $animal;
    }

    public function render(AnimalProfileQueryService $queryService)
    {
        $animal = Animal::query()
            ->ownedBy(auth()->id())
            ->findOrFail($this->animal->id);

        $profile = $queryService->build(animal: $animal);

        return view('livewire.animals.animal-profile-page', [
            'animal' => $animal,
            'profile' => $profile,
        ]);
    }
}
