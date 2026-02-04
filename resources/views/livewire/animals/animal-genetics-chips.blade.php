<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Genetyka</h2>

        @if(! $isReadOnly)
            <form wire:submit="save" class="vstack gap-2 mb-3">
                <input
                    type="text"
                    list="gene-dictionary-{{ $this->getId() }}"
                    class="form-control @error('form.gene_name') is-invalid @enderror"
                    wire:model="form.gene_name"
                    placeholder="Nazwa genu (np. Amel)"
                >
                <datalist id="gene-dictionary-{{ $this->getId() }}">
                    @foreach($dictionary as $item)
                        <option value="{{ $item->name }}">{{ $item->gene_code }}</option>
                    @endforeach
                </datalist>
                @error('form.gene_name') <div class="invalid-feedback">{{ $message }}</div> @enderror

                <div class="d-flex gap-2">
                    <select class="form-select @error('form.type') is-invalid @enderror" wire:model="form.type">
                        <option value="v">v - homozygota</option>
                        <option value="h">h - heterozygota</option>
                        <option value="p">p - possible het</option>
                    </select>
                    <button class="btn btn-primary" type="submit">{{ $editingId ? 'Zapisz' : 'Dodaj' }}</button>
                </div>
                @error('form.type') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                @if($editingId)
                    <button class="btn btn-outline-secondary btn-sm align-self-start" type="button" wire:click="cancelEdit">Anuluj edycje</button>
                @endif
            </form>
        @else
            <div class="alert alert-secondary small mb-3">Genetyka pochodzi z API i jest tylko do odczytu.</div>
        @endif

        <div class="vstack gap-2">
            @forelse($genotypes as $genotype)
                <div class="genotype-chip-row">
                    <div class="small fw-semibold">
                        @php
                            $type = strtolower((string) $genotype->type);
                            $prefix = $type === 'h' ? 'het. ' : ($type === 'p' ? 'ph ' : '');
                        @endphp
                        {{ $prefix }}{{ $genotype->genotypeCategory?->name ?? 'Gen' }}
                    </div>
                    @if(! $isReadOnly)
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-secondary" wire:click="startEdit({{ $genotype->id }})">Edytuj</button>
                            <button class="btn btn-sm btn-outline-danger" wire:click="delete({{ $genotype->id }})">Usun</button>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-muted small mb-0">Brak wpisanych genow.</p>
            @endforelse
        </div>
    </div>
</div>
