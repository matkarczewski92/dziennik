<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use App\Models\AnimalOffer;
use App\Services\OfferService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class AnimalOfferWidget extends Component
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

    public function save(OfferService $offerService): void
    {
        if (! Schema::hasTable('animal_offers')) {
            session()->flash('error', 'Tabela ofert nie jest jeszcze dostepna.');

            return;
        }

        $data = $this->validate([
            'form.price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'form.sold_date' => ['nullable', 'date'],
        ]);

        $animal = $this->animal();
        $this->authorize('update', $animal);

        $offer = null;
        if ($this->editingId) {
            $offer = AnimalOffer::query()
                ->ownedBy(auth()->id())
                ->where('animal_id', $animal->id)
                ->findOrFail($this->editingId);
        }

        $offerService->save(auth()->user(), $animal, $data['form'], $offer);

        $this->resetForm();
        session()->flash('success', 'Status oferty zostal zapisany.');
        $this->dispatch('animal-profile-refresh');
    }

    public function startEdit(int $offerId): void
    {
        $offer = AnimalOffer::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal()->id)
            ->findOrFail($offerId);

        $this->editingId = $offer->id;
        $this->form = [
            'price' => $offer->price !== null ? (float) $offer->price : null,
            'sold_date' => $offer->sold_date?->toDateString(),
        ];
    }

    public function markAvailable(): void
    {
        if (! $this->editingId) {
            return;
        }

        $offer = AnimalOffer::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal()->id)
            ->findOrFail($this->editingId);

        $offer->update(['sold_date' => null]);

        $this->form['sold_date'] = null;
        session()->flash('success', 'Oferta oznaczona jako dostepna.');
        $this->dispatch('animal-profile-refresh');
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function delete(int $offerId, OfferService $offerService): void
    {
        $offer = AnimalOffer::query()
            ->ownedBy(auth()->id())
            ->where('animal_id', $this->animal()->id)
            ->findOrFail($offerId);

        $offerService->delete(auth()->user(), $offer);

        if ($this->editingId === $offerId) {
            $this->resetForm();
        }

        session()->flash('success', 'Oferta zostala usunieta.');
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
            'price' => null,
            'sold_date' => null,
        ];
        $this->resetValidation();
    }

    public function render()
    {
        $hasTable = Schema::hasTable('animal_offers');
        $offer = null;

        if ($hasTable) {
            $offer = AnimalOffer::query()
                ->ownedBy(auth()->id())
                ->where('animal_id', $this->animal()->id)
                ->orderByDesc('id')
                ->first();
        }

        return view('livewire.animals.animal-offer-widget', [
            'hasOfferTable' => $hasTable,
            'offer' => $offer,
        ]);
    }
}

