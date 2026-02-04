<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use App\Models\AnimalGenotype;
use App\Models\AnimalGenotypeCategory;
use App\Models\Feed;
use App\Models\Feeding;
use App\Models\Note;
use App\Models\Photo;
use App\Models\Shed;
use App\Models\Weight;
use App\Services\Animal\AnimalEventProjector;
use App\Services\FeedingService;
use App\Services\NoteService;
use App\Services\PhotoService;
use App\Services\ShedService;
use App\Services\WeightService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Profile extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;
    use WithPagination;

    public Animal $animal;

    public string $activeTab = 'feedings';

    public array $feedingForm = [];

    public ?int $editingFeedingId = null;

    public array $weightForm = [];

    public ?int $editingWeightId = null;

    public array $shedForm = [];

    public ?int $editingShedId = null;

    public array $noteForm = [];

    public ?int $editingNoteId = null;

    public array $genotypeForm = [];

    public ?int $editingGenotypeId = null;

    public $photoUpload;

    public ?string $photo_taken_at = null;

    public ?string $photo_notes = null;

    public bool $showPhotoModal = false;

    public ?int $activePhotoId = null;

    public function mount(Animal $animal): void
    {
        $this->authorize('view', $animal);
        $this->animal = $animal;

        $this->resetFeedingForm();
        $this->resetWeightForm();
        $this->resetShedForm();
        $this->resetNoteForm();
        $this->resetGenotypeForm();
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['genetics', 'feedings', 'weights', 'sheds', 'notes', 'photos'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function addGenotype(AnimalEventProjector $eventProjector): void
    {
        $data = $this->validate([
            'genotypeForm.gene_name' => ['required', 'string', 'max:255'],
            'genotypeForm.type' => ['required', 'in:v,h,p'],
        ]);

        $geneName = trim((string) $data['genotypeForm']['gene_name']);
        $category = AnimalGenotypeCategory::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($geneName)])
            ->first();

        if (! $category) {
            $this->addError('genotypeForm.gene_name', 'Nie znaleziono takiego genu w slowniku.');
            return;
        }

        if ($this->editingGenotypeId) {
            $genotype = AnimalGenotype::query()
                ->where('animal_id', $this->animal->id)
                ->findOrFail($this->editingGenotypeId);

            $genotype->update([
                'genotype_id' => (int) $category->id,
                'type' => $data['genotypeForm']['type'],
            ]);
            $eventProjector->projectGenotype($genotype);
            session()->flash('success', 'Genotyp zostal zaktualizowany.');
        } else {
            $genotype = AnimalGenotype::query()->updateOrCreate(
                [
                    'animal_id' => $this->animal->id,
                    'genotype_id' => (int) $category->id,
                ],
                [
                    'type' => $data['genotypeForm']['type'],
                ],
            );
            $eventProjector->projectGenotype($genotype);
            session()->flash('success', 'Genotyp zostal zapisany.');
        }

        $this->resetGenotypeForm();
    }

    public function startEditGenotype(int $genotypePivotId): void
    {
        $genotype = AnimalGenotype::query()
            ->where('animal_id', $this->animal->id)
            ->with('genotypeCategory')
            ->findOrFail($genotypePivotId);

        $this->editingGenotypeId = $genotype->id;
        $this->genotypeForm = [
            'gene_name' => (string) $genotype->genotypeCategory?->name,
            'type' => (string) $genotype->type,
        ];
    }

    public function cancelEditGenotype(): void
    {
        $this->resetGenotypeForm();
    }

    public function deleteGenotype(int $genotypePivotId, AnimalEventProjector $eventProjector): void
    {
        $genotype = AnimalGenotype::query()
            ->where('animal_id', $this->animal->id)
            ->findOrFail($genotypePivotId);

        $genotype->delete();
        $eventProjector->removeGenotype($genotype);
        if ($this->editingGenotypeId === $genotypePivotId) {
            $this->resetGenotypeForm();
        }
        session()->flash('success', 'Usunieto genotyp.');
    }

    public function addFeeding(FeedingService $feedingService): void
    {
        $data = $this->validate([
            'feedingForm.fed_at' => ['required', 'date'],
            'feedingForm.feed_id' => ['required', 'integer', 'exists:feeds,id'],
            'feedingForm.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'feedingForm.notes' => ['nullable', 'string'],
        ]);

        $payload = [
            ...$data['feedingForm'],
            'prey_weight_grams' => null,
        ];

        if ($this->editingFeedingId) {
            $feeding = Feeding::query()
                ->ownedBy(auth()->id())
                ->where('animal_id', $this->animal->id)
                ->findOrFail($this->editingFeedingId);

            $this->authorize('update', $feeding);
            $feedingService->update(auth()->user(), $feeding, $payload);
            session()->flash('success', 'Wpis karmienia zostal zaktualizowany.');
        } else {
            $feedingService->create(auth()->user(), $this->animal, $payload);
            session()->flash('success', 'Dodano karmienie.');
        }

        $this->resetFeedingForm();
    }

    public function startEditFeeding(int $feedingId): void
    {
        $feeding = Feeding::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal->id)
            ->findOrFail($feedingId);

        $this->authorize('update', $feeding);
        $this->editingFeedingId = $feeding->id;
        $this->feedingForm = [
            'fed_at' => $feeding->fed_at?->toDateString(),
            'feed_id' => $feeding->feed_id,
            'quantity' => (int) $feeding->quantity,
            'notes' => $feeding->notes ?? '',
        ];
    }

    public function cancelEditFeeding(): void
    {
        $this->resetFeedingForm();
    }

    public function deleteFeeding(int $feedingId, FeedingService $feedingService): void
    {
        $feeding = Feeding::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal->id)
            ->findOrFail($feedingId);

        $this->authorize('delete', $feeding);
        $feedingService->delete(auth()->user(), $feeding);
        if ($this->editingFeedingId === $feedingId) {
            $this->resetFeedingForm();
        }
        session()->flash('success', 'Usunieto wpis karmienia.');
    }

    public function addWeight(WeightService $weightService): void
    {
        $data = $this->validate([
            'weightForm.measured_at' => ['required', 'date'],
            'weightForm.weight_grams' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'weightForm.notes' => ['nullable', 'string'],
        ]);

        if ($this->editingWeightId) {
            $weight = Weight::query()
                ->ownedBy(auth()->id())
                ->where('animal_id', $this->animal->id)
                ->findOrFail($this->editingWeightId);

            $this->authorize('update', $weight);
            $weightService->update(auth()->user(), $weight, $data['weightForm']);
            session()->flash('success', 'Wpis wazenia zostal zaktualizowany.');
        } else {
            $weightService->create(auth()->user(), $this->animal, $data['weightForm']);
            session()->flash('success', 'Dodano wazenie.');
        }

        $this->resetWeightForm();
    }

    public function startEditWeight(int $weightId): void
    {
        $weight = Weight::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal->id)
            ->findOrFail($weightId);

        $this->authorize('update', $weight);
        $this->editingWeightId = $weight->id;
        $this->weightForm = [
            'measured_at' => $weight->measured_at?->toDateString(),
            'weight_grams' => $weight->weight_grams !== null ? (float) $weight->weight_grams : null,
            'notes' => $weight->notes ?? '',
        ];
    }

    public function cancelEditWeight(): void
    {
        $this->resetWeightForm();
    }

    public function deleteWeight(int $weightId, WeightService $weightService): void
    {
        $weight = Weight::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal->id)
            ->findOrFail($weightId);

        $this->authorize('delete', $weight);
        $weightService->delete(auth()->user(), $weight);
        if ($this->editingWeightId === $weightId) {
            $this->resetWeightForm();
        }
        session()->flash('success', 'Usunieto wpis wazenia.');
    }

    public function addShed(ShedService $shedService): void
    {
        $data = $this->validate([
            'shedForm.shed_at' => ['required', 'date'],
            'shedForm.quality' => ['nullable', 'string', 'max:30'],
            'shedForm.notes' => ['nullable', 'string'],
        ]);

        if ($this->editingShedId) {
            $shed = Shed::query()
                ->ownedBy(auth()->id())
                ->where('animal_id', $this->animal->id)
                ->findOrFail($this->editingShedId);

            $this->authorize('update', $shed);
            $shedService->update(auth()->user(), $shed, $data['shedForm']);
            session()->flash('success', 'Wpis wylinki zostal zaktualizowany.');
        } else {
            $shedService->create(auth()->user(), $this->animal, $data['shedForm']);
            session()->flash('success', 'Dodano wpis wylinki.');
        }

        $this->resetShedForm();
    }

    public function startEditShed(int $shedId): void
    {
        $shed = Shed::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal->id)
            ->findOrFail($shedId);

        $this->authorize('update', $shed);
        $this->editingShedId = $shed->id;
        $this->shedForm = [
            'shed_at' => $shed->shed_at?->toDateString(),
            'quality' => $shed->quality ?? '',
            'notes' => $shed->notes ?? '',
        ];
    }

    public function cancelEditShed(): void
    {
        $this->resetShedForm();
    }

    public function deleteShed(int $shedId, ShedService $shedService): void
    {
        $shed = Shed::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal->id)
            ->findOrFail($shedId);

        $this->authorize('delete', $shed);
        $shedService->delete(auth()->user(), $shed);
        if ($this->editingShedId === $shedId) {
            $this->resetShedForm();
        }
        session()->flash('success', 'Usunieto wpis wylinki.');
    }

    public function addNote(NoteService $noteService): void
    {
        $data = $this->validate([
            'noteForm.body' => ['required', 'string'],
            'noteForm.is_pinned' => ['nullable', 'boolean'],
        ]);

        if ($this->editingNoteId) {
            $note = Note::query()
                ->ownedBy(auth()->id())
                ->where('animal_id', $this->animal->id)
                ->findOrFail($this->editingNoteId);

            $this->authorize('update', $note);
            $noteService->update(auth()->user(), $note, $data['noteForm']);
            session()->flash('success', 'Notatka zostala zaktualizowana.');
        } else {
            $noteService->create(auth()->user(), $this->animal, $data['noteForm']);
            session()->flash('success', 'Dodano notatke.');
        }

        $this->resetNoteForm();
    }

    public function startEditNote(int $noteId): void
    {
        $note = Note::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal->id)
            ->findOrFail($noteId);

        $this->authorize('update', $note);
        $this->editingNoteId = $note->id;
        $this->noteForm = [
            'body' => $note->body,
            'is_pinned' => (bool) $note->is_pinned,
        ];
    }

    public function cancelEditNote(): void
    {
        $this->resetNoteForm();
    }

    public function deleteNote(int $noteId, NoteService $noteService): void
    {
        $note = Note::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal->id)
            ->findOrFail($noteId);

        $this->authorize('delete', $note);
        $noteService->delete(auth()->user(), $note);
        if ($this->editingNoteId === $noteId) {
            $this->resetNoteForm();
        }
        session()->flash('success', 'Usunieto notatke.');
    }

    public function uploadPhoto(PhotoService $photoService): void
    {
        $this->validate([
            'photoUpload' => ['required', 'image', 'max:25600'],
            'photo_taken_at' => ['nullable', 'date'],
            'photo_notes' => ['nullable', 'string'],
        ]);

        $photoService->store(auth()->user(), $this->animal, $this->photoUpload, [
            'taken_at' => $this->photo_taken_at,
            'notes' => $this->photo_notes,
        ]);

        $this->reset('photoUpload', 'photo_taken_at', 'photo_notes');
        session()->flash('success', 'Zdjecie zostalo dodane.');
    }

    public function deletePhoto(int $photoId, PhotoService $photoService): void
    {
        $photo = Photo::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal->id)
            ->findOrFail($photoId);

        $this->authorize('delete', $photo);
        $photoService->delete(auth()->user(), $photo);
        if ($this->activePhotoId === $photoId) {
            $this->activePhotoId = null;
            $this->showPhotoModal = false;
        }
        session()->flash('success', 'Zdjecie zostalo usuniete.');
    }

    public function openPhotoModal(int $photoId): void
    {
        $exists = Photo::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal->id)
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
            ->where('animal_id', $this->animal->id)
            ->latest()
            ->pluck('id')
            ->all();
    }

    protected function resetFeedingForm(): void
    {
        $defaultFeedId = Feed::query()->orderBy('id')->value('id');
        $this->editingFeedingId = null;

        $this->feedingForm = [
            'fed_at' => now()->toDateString(),
            'feed_id' => $defaultFeedId ? (int) $defaultFeedId : null,
            'quantity' => 1,
            'notes' => '',
        ];
    }

    protected function resetWeightForm(): void
    {
        $this->editingWeightId = null;

        $this->weightForm = [
            'measured_at' => now()->toDateString(),
            'weight_grams' => null,
            'notes' => '',
        ];
    }

    protected function resetShedForm(): void
    {
        $this->editingShedId = null;

        $this->shedForm = [
            'shed_at' => now()->toDateString(),
            'quality' => '',
            'notes' => '',
        ];
    }

    protected function resetNoteForm(): void
    {
        $this->editingNoteId = null;

        $this->noteForm = [
            'body' => '',
            'is_pinned' => false,
        ];
    }

    protected function resetGenotypeForm(): void
    {
        $this->editingGenotypeId = null;

        $this->genotypeForm = [
            'gene_name' => '',
            'type' => 'h',
        ];
    }

    public function render()
    {
        $this->animal = Animal::query()
            ->ownedBy(auth()->id())
            ->findOrFail($this->animal->id);

        return view('livewire.animals.profile', [
            'animal' => $this->animal,
            'genotypeCategories' => AnimalGenotypeCategory::query()->orderBy('name')->get(),
            'genotypes' => AnimalGenotype::query()
                ->where('animal_id', $this->animal->id)
                ->with('genotypeCategory')
                ->orderByDesc('id')
                ->get(),
            'feedOptions' => Feed::query()->orderBy('id')->get(),
            'feedings' => Feeding::query()
                ->ownedBy(auth()->id())
                ->where('animal_id', $this->animal->id)
                ->with('feed')
                ->latest('fed_at')
                ->get(),
            'weights' => Weight::query()
                ->ownedBy(auth()->id())
                ->where('animal_id', $this->animal->id)
                ->latest('measured_at')
                ->paginate(10, ['*'], 'weightsPage'),
            'sheds' => Shed::query()
                ->ownedBy(auth()->id())
                ->where('animal_id', $this->animal->id)
                ->latest('shed_at')
                ->paginate(10, ['*'], 'shedsPage'),
            'notes' => Note::query()
                ->ownedBy(auth()->id())
                ->where('animal_id', $this->animal->id)
                ->orderByDesc('is_pinned')
                ->latest()
                ->get(),
            'photos' => Photo::query()->ownedBy(auth()->id())->where('animal_id', $this->animal->id)->latest()->get(),
            'activePhoto' => $this->activePhotoId
                ? Photo::query()
                    ->ownedBy(auth()->id())
                    ->where('animal_id', $this->animal->id)
                    ->find($this->activePhotoId)
                : null,
        ]);
    }
}
