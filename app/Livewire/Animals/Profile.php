<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use App\Models\AnimalGenotype;
use App\Models\AnimalGenotypeCategory;
use App\Models\Feeding;
use App\Models\Note;
use App\Models\Photo;
use App\Models\Shed;
use App\Models\Weight;
use App\Services\FeedingService;
use App\Services\NoteService;
use App\Services\PhotoService;
use App\Services\ShedService;
use App\Services\WeightService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;

class Profile extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public Animal $animal;

    public string $activeTab = 'feedings';

    public array $feedingForm = [];

    public array $weightForm = [];

    public array $shedForm = [];

    public array $noteForm = [];

    public array $genotypeForm = [];

    public $photoUpload;

    public ?string $photo_taken_at = null;

    public ?string $photo_notes = null;

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

    public function addGenotype(): void
    {
        $data = $this->validate([
            'genotypeForm.genotype_id' => ['required', 'integer', 'exists:animal_genotype_category,id'],
            'genotypeForm.type' => ['required', 'in:v,h,p'],
        ]);

        AnimalGenotype::query()->updateOrCreate(
            [
                'animal_id' => $this->animal->id,
                'genotype_id' => (int) $data['genotypeForm']['genotype_id'],
            ],
            [
                'type' => $data['genotypeForm']['type'],
            ],
        );

        $this->resetGenotypeForm();
        session()->flash('success', 'Genotyp zostal zapisany.');
    }

    public function deleteGenotype(int $genotypePivotId): void
    {
        $genotype = AnimalGenotype::query()
            ->where('animal_id', $this->animal->id)
            ->findOrFail($genotypePivotId);

        $genotype->delete();
        session()->flash('success', 'Usunieto genotyp.');
    }

    public function addFeeding(FeedingService $feedingService): void
    {
        $data = $this->validate([
            'feedingForm.fed_at' => ['required', 'date'],
            'feedingForm.prey' => ['required', 'string', 'max:255'],
            'feedingForm.prey_weight_grams' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'feedingForm.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'feedingForm.notes' => ['nullable', 'string'],
        ]);

        $feedingService->create(auth()->user(), $this->animal, $data['feedingForm']);
        $this->resetFeedingForm();
        session()->flash('success', 'Dodano karmienie.');
    }

    public function deleteFeeding(int $feedingId, FeedingService $feedingService): void
    {
        $feeding = Feeding::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal->id)
            ->findOrFail($feedingId);

        $this->authorize('delete', $feeding);
        $feedingService->delete(auth()->user(), $feeding);
        session()->flash('success', 'Usunieto wpis karmienia.');
    }

    public function addWeight(WeightService $weightService): void
    {
        $data = $this->validate([
            'weightForm.measured_at' => ['required', 'date'],
            'weightForm.weight_grams' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'weightForm.notes' => ['nullable', 'string'],
        ]);

        $weightService->create(auth()->user(), $this->animal, $data['weightForm']);
        $this->resetWeightForm();
        session()->flash('success', 'Dodano wazenie.');
    }

    public function deleteWeight(int $weightId, WeightService $weightService): void
    {
        $weight = Weight::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal->id)
            ->findOrFail($weightId);

        $this->authorize('delete', $weight);
        $weightService->delete(auth()->user(), $weight);
        session()->flash('success', 'Usunieto wpis wazenia.');
    }

    public function addShed(ShedService $shedService): void
    {
        $data = $this->validate([
            'shedForm.shed_at' => ['required', 'date'],
            'shedForm.quality' => ['nullable', 'string', 'max:30'],
            'shedForm.notes' => ['nullable', 'string'],
        ]);

        $shedService->create(auth()->user(), $this->animal, $data['shedForm']);
        $this->resetShedForm();
        session()->flash('success', 'Dodano wpis wylinki.');
    }

    public function deleteShed(int $shedId, ShedService $shedService): void
    {
        $shed = Shed::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal->id)
            ->findOrFail($shedId);

        $this->authorize('delete', $shed);
        $shedService->delete(auth()->user(), $shed);
        session()->flash('success', 'Usunieto wpis wylinki.');
    }

    public function addNote(NoteService $noteService): void
    {
        $data = $this->validate([
            'noteForm.body' => ['required', 'string'],
            'noteForm.is_pinned' => ['nullable', 'boolean'],
        ]);

        $noteService->create(auth()->user(), $this->animal, $data['noteForm']);
        $this->resetNoteForm();
        session()->flash('success', 'Dodano notatke.');
    }

    public function deleteNote(int $noteId, NoteService $noteService): void
    {
        $note = Note::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal->id)
            ->findOrFail($noteId);

        $this->authorize('delete', $note);
        $noteService->delete(auth()->user(), $note);
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
        session()->flash('success', 'Zdjecie zostalo usuniete.');
    }

    protected function resetFeedingForm(): void
    {
        $this->feedingForm = [
            'fed_at' => now()->toDateString(),
            'prey' => '',
            'prey_weight_grams' => null,
            'quantity' => 1,
            'notes' => '',
        ];
    }

    protected function resetWeightForm(): void
    {
        $this->weightForm = [
            'measured_at' => now()->toDateString(),
            'weight_grams' => null,
            'notes' => '',
        ];
    }

    protected function resetShedForm(): void
    {
        $this->shedForm = [
            'shed_at' => now()->toDateString(),
            'quality' => '',
            'notes' => '',
        ];
    }

    protected function resetNoteForm(): void
    {
        $this->noteForm = [
            'body' => '',
            'is_pinned' => false,
        ];
    }

    protected function resetGenotypeForm(): void
    {
        $this->genotypeForm = [
            'genotype_id' => null,
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
            'feedings' => Feeding::query()->ownedBy(auth()->id())->where('animal_id', $this->animal->id)->latest('fed_at')->get(),
            'weights' => Weight::query()->ownedBy(auth()->id())->where('animal_id', $this->animal->id)->latest('measured_at')->get(),
            'sheds' => Shed::query()->ownedBy(auth()->id())->where('animal_id', $this->animal->id)->latest('shed_at')->get(),
            'notes' => Note::query()->ownedBy(auth()->id())->where('animal_id', $this->animal->id)->latest()->get(),
            'photos' => Photo::query()->ownedBy(auth()->id())->where('animal_id', $this->animal->id)->latest()->get(),
        ]);
    }
}
