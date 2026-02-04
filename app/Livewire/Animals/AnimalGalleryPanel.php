<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use App\Models\Photo;
use App\Services\PhotoService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;

class AnimalGalleryPanel extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public int $animalId;

    public bool $showManageModal = false;

    public bool $showPhotoModal = false;

    public ?int $activePhotoId = null;

    public $photoUpload;

    public ?string $photo_taken_at = null;

    public ?string $photo_notes = null;

    public function mount(int $animalId): void
    {
        $this->animalId = $animalId;
    }

    public function openManageModal(): void
    {
        $this->showManageModal = true;
    }

    public function closeManageModal(): void
    {
        $this->showManageModal = false;
        $this->resetValidation();
    }

    public function uploadPhoto(PhotoService $photoService): void
    {
        $this->validate([
            'photoUpload' => ['required', 'image', 'max:25600'],
            'photo_taken_at' => ['nullable', 'date'],
            'photo_notes' => ['nullable', 'string'],
        ]);

        $photoService->store(auth()->user(), $this->animal(), $this->photoUpload, [
            'taken_at' => $this->photo_taken_at,
            'notes' => $this->photo_notes,
        ]);

        $this->reset('photoUpload', 'photo_taken_at', 'photo_notes');
        session()->flash('success', 'Zdjecie zostalo dodane.');
        $this->dispatch('animal-profile-refresh');
    }

    public function deletePhoto(int $photoId, PhotoService $photoService): void
    {
        $photo = Photo::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal()->id)
            ->findOrFail($photoId);

        $this->authorize('delete', $photo);
        $photoService->delete(auth()->user(), $photo);

        if ($this->activePhotoId === $photoId) {
            $this->activePhotoId = null;
            $this->showPhotoModal = false;
        }

        session()->flash('success', 'Zdjecie zostalo usuniete.');
        $this->dispatch('animal-profile-refresh');
    }

    public function openPhotoModal(int $photoId): void
    {
        $exists = Photo::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal()->id)
            ->whereKey($photoId)
            ->exists();

        if (! $exists) {
            return;
        }

        $this->activePhotoId = $photoId;
        $this->showPhotoModal = true;
    }

    public function closePhotoModal(): void
    {
        $this->showPhotoModal = false;
        $this->activePhotoId = null;
    }

    public function showNextPhoto(): void
    {
        $ids = $this->photoIds();
        if ($ids === [] || ! $this->activePhotoId) {
            return;
        }

        $currentIndex = array_search($this->activePhotoId, $ids, true);
        if ($currentIndex === false) {
            $this->activePhotoId = $ids[0];
            return;
        }

        $nextIndex = ($currentIndex + 1) % count($ids);
        $this->activePhotoId = $ids[$nextIndex];
    }

    public function showPreviousPhoto(): void
    {
        $ids = $this->photoIds();
        if ($ids === [] || ! $this->activePhotoId) {
            return;
        }

        $currentIndex = array_search($this->activePhotoId, $ids, true);
        if ($currentIndex === false) {
            $this->activePhotoId = $ids[0];
            return;
        }

        $previousIndex = ($currentIndex - 1 + count($ids)) % count($ids);
        $this->activePhotoId = $ids[$previousIndex];
    }

    protected function photoIds(): array
    {
        return Photo::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal()->id)
            ->latest()
            ->pluck('id')
            ->all();
    }

    protected function animal(): Animal
    {
        return Animal::query()
            ->ownedBy(auth()->id())
            ->findOrFail($this->animalId);
    }

    public function render()
    {
        $animal = $this->animal();

        return view('livewire.animals.animal-gallery-panel', [
            'photos' => Photo::query()
                ->ownedBy(auth()->id())
                ->where('animal_id', $animal->id)
                ->latest()
                ->get(),
            'activePhoto' => $this->activePhotoId
                ? Photo::query()
                    ->ownedBy(auth()->id())
                    ->where('animal_id', $animal->id)
                    ->find($this->activePhotoId)
                : null,
        ]);
    }
}

