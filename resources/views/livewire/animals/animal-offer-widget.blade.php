<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Status oferty</h2>

        @if(! $hasOfferTable)
            <div class="alert alert-warning mb-0">Tabela `animal_offers` nie istnieje. Uruchom migracje.</div>
        @else
            @if($offer)
                <div class="border rounded p-2 mb-3">
                    <div class="small fw-semibold">Aktualny wpis</div>
                    <div class="small">Cena: {{ number_format((float) $offer->price, 2, ',', ' ') }} PLN</div>
                    <div class="small">Status: {{ $offer->sold_date ? 'Sprzedany' : 'Dostepny' }}</div>
                    <div class="small">Data sprzedazy: {{ $offer->sold_date?->format('Y-m-d') ?: '-' }}</div>
                    <div class="d-flex gap-1 mt-2">
                        <button class="btn btn-sm btn-outline-secondary" wire:click="startEdit({{ $offer->id }})">Edytuj</button>
                        <button class="btn btn-sm btn-outline-danger" wire:click="delete({{ $offer->id }})">Usun</button>
                    </div>
                </div>
            @endif

            <form wire:submit="save" class="vstack gap-2">
                <input type="number" step="0.01" class="form-control @error('form.price') is-invalid @enderror" wire:model="form.price" placeholder="Cena">
                @error('form.price') <div class="invalid-feedback">{{ $message }}</div> @enderror

                <input type="date" class="form-control @error('form.sold_date') is-invalid @enderror" wire:model="form.sold_date">
                @error('form.sold_date') <div class="invalid-feedback">{{ $message }}</div> @enderror

                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-primary btn-sm" type="submit">{{ $editingId ? 'Zapisz' : 'Dodaj oferte' }}</button>
                    @if($editingId)
                        <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="markAvailable">Oznacz jako dostepny</button>
                        <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="cancelEdit">Anuluj</button>
                    @endif
                </div>
            </form>
        @endif
    </div>
</div>

