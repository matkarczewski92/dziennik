<div>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Dane podstawowe</h2>
            <dl class="animal-details-grid mb-0">
                <dt>Plec</dt>
                <dd>{{ $this->sexLabel() }}</dd>

                <dt>Data wyklucia</dt>
                <dd>{{ $identity['hatch_date'] ?: '-' }}</dd>

                <dt>Data zakupu</dt>
                <dd>{{ $identity['acquired_at'] ?: '-' }}</dd>

                <dt>Waga biezaca</dt>
                <dd>{{ $identity['current_weight_grams'] !== null ? number_format((float) $identity['current_weight_grams'], 2, ',', ' ') . ' g' : '-' }}</dd>

                <dt>Interwal karmien</dt>
                <dd>{{ $identity['feeding_interval_days'] ?: '-' }} dni</dd>
            </dl>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0">Notatka ogolna</h2>
                <button type="button" class="btn btn-outline-light btn-sm" wire:click="openNoteEditModal">Edytuj notatke</button>
            </div>
            <p class="small mb-0">{{ $identity['notes'] ?: 'Brak notatki.' }}</p>
        </div>
    </div>

    @if($showNoteEditModal)
        <div class="livewire-modal-backdrop">
            <div class="livewire-modal">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Edycja notatki ogolnej</h3>
                    <button class="btn-close" type="button" wire:click="closeNoteEditModal"></button>
                </div>

                <form wire:submit="saveNote" class="vstack gap-2">
                    <textarea class="form-control @error('noteText') is-invalid @enderror" rows="6" wire:model="noteText" placeholder="Wpisz notatke"></textarea>
                    @error('noteText') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeNoteEditModal">Anuluj</button>
                        <button type="submit" class="btn btn-primary">Zapisz</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
