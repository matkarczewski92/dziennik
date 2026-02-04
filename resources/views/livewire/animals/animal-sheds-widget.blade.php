<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Wylinki</h2>

        <form wire:submit="save" class="vstack gap-2 mb-3">
            <input type="date" class="form-control @error('form.shed_at') is-invalid @enderror" wire:model="form.shed_at">
            @error('form.shed_at') <div class="invalid-feedback">{{ $message }}</div> @enderror

            <input type="text" class="form-control @error('form.quality') is-invalid @enderror" wire:model="form.quality" placeholder="Jakosc (np. pelna)">
            @error('form.quality') <div class="invalid-feedback">{{ $message }}</div> @enderror

            <input type="text" class="form-control @error('form.notes') is-invalid @enderror" wire:model="form.notes" placeholder="Notatka">
            @error('form.notes') <div class="invalid-feedback">{{ $message }}</div> @enderror

            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary btn-sm" type="submit">{{ $editingId ? 'Zapisz' : 'Dodaj' }}</button>
                @if($editingId)
                    <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="cancelEdit">Anuluj</button>
                @endif
            </div>
        </form>

        <div class="vstack gap-2">
            @forelse($sheds as $shed)
                <div class="border rounded p-2 @if($editingId === $shed->id) border-info @endif">
                    <div class="d-flex justify-content-between gap-2 align-items-start">
                        <div>
                            <div class="small fw-semibold">{{ $shed->shed_at?->format('Y-m-d') }}</div>
                            <div class="small text-muted">{{ $shed->quality ?: 'brak oceny' }}</div>
                            @if($shed->notes)
                                <div class="small">{{ $shed->notes }}</div>
                            @endif
                        </div>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-secondary" wire:click="startEdit({{ $shed->id }})">Edytuj</button>
                            <button class="btn btn-sm btn-outline-danger" wire:click="delete({{ $shed->id }})">Usun</button>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-muted small mb-0">Brak wpisow o wylinkach.</p>
            @endforelse
        </div>

        @include('components.pagination.inline-controls', ['paginator' => $sheds])
    </div>
</div>
