<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use App\Services\Animal\AnimalProfileQueryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class AnimalProfilePage extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public Animal $animal;

    #[On('animal-profile-refresh')]
    public function refreshProfile(): void
    {
        // Listener used to force rerender after nested component updates.
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

        $profile = $queryService->build(
            animal: $animal,
            shedsPage: $this->getPage('shedsPage'),
            shedsPerPage: 8,
        );

        return view('livewire.animals.animal-profile-page', [
            'animal' => $animal,
            'profile' => $profile,
        ]);
    }
}
