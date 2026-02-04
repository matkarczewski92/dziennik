<div class="container-fluid px-0">
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Zwierzeta</h1>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm" wire:click="openCreateModal">Dodaj recznie</button>
            <button class="btn btn-outline-primary btn-sm" wire:click="openImportModal">Import po secret_tag</button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <input type="text" class="form-control" placeholder="Szukaj po nazwie, gatunku, morph, secret_tag..." wire:model.live.debounce.400ms="search">
        </div>
    </div>

    <div class="row g-3">
        @forelse($animals as $animal)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h2 class="h6 mb-0">{{ $animal->name }}</h2>
                            @if($animal->imported_from_api)
                                <span class="badge text-bg-info">API</span>
                            @endif
                        </div>
                        <div class="text-muted small mb-3">
                            {{ $animal->species?->name ?: 'brak gatunku' }} | {{ $animal->morph ?: 'brak morph' }}
                        </div>
                        <div class="small mb-3">Interwal karmienia: {{ $animal->feeding_interval_days }} dni</div>
                        <div class="mt-auto d-flex flex-wrap gap-2">
                            <a class="btn btn-outline-primary btn-sm" href="{{ route('animals.show', $animal) }}">Profil</a>
                            <button class="btn btn-outline-secondary btn-sm" wire:click="openEditModal({{ $animal->id }})">Edytuj</button>
                            <button class="btn btn-outline-danger btn-sm" wire:click="confirmDelete({{ $animal->id }})">Usun</button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-light border">Brak zwierzat. Dodaj pierwsze zwierze recznie lub przez import.</div>
            </div>
        @endforelse
    </div>

    <div class="mt-3">
        {{ $animals->links() }}
    </div>

    @if($showAnimalModal)
        <div class="livewire-modal-backdrop">
            <div class="livewire-modal">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">{{ $editingId ? 'Edycja zwierzecia' : 'Nowe zwierze' }}</h2>
                    <button class="btn-close" type="button" wire:click="$set('showAnimalModal', false)"></button>
                </div>

                <form wire:submit="saveAnimal" class="vstack gap-2">
                    <div>
                        <label class="form-label">Nazwa</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Gatunek</label>
                            <select class="form-select @error('species_id') is-invalid @enderror" wire:model="species_id">
                                <option value="">-- wybierz gatunek --</option>
                                @foreach($speciesOptions as $speciesOption)
                                    <option value="{{ $speciesOption->id }}">{{ $speciesOption->name }}</option>
                                @endforeach
                            </select>
                            @error('species_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Plec</label>
                            <select class="form-select @error('sex') is-invalid @enderror" wire:model="sex">
                                <option value="unknown">Nieznana</option>
                                <option value="male">Samiec</option>
                                <option value="female">Samica</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Data wyklucia</label>
                            <input type="date" class="form-control @error('hatch_date') is-invalid @enderror" wire:model="hatch_date">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Waga (g)</label>
                            <input type="number" step="0.01" class="form-control @error('current_weight_grams') is-invalid @enderror" wire:model="current_weight_grams">
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Data dolaczenia</label>
                            <input type="date" class="form-control @error('acquired_at') is-invalid @enderror" wire:model="acquired_at">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Interwal karmienia (dni)</label>
                            <input type="number" min="1" max="90" class="form-control @error('feeding_interval_days') is-invalid @enderror" wire:model="feeding_interval_days">
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Notatka</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" wire:model="notes" rows="3"></textarea>
                    </div>

                    <div>
                        <label class="form-label">Morphy</label>
                        <select
                            multiple
                            class="form-select @error('selectedMorphIds') is-invalid @enderror @error('selectedMorphIds.*') is-invalid @enderror"
                            wire:model="selectedMorphIds"
                            size="8"
                        >
                            @foreach($morphOptions as $morphOption)
                                <option value="{{ $morphOption->id }}">{{ $morphOption->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Mozesz wybrac wiele pozycji (Ctrl/Cmd + klik).</div>
                        @error('selectedMorphIds') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @error('selectedMorphIds.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-2">
                        <button type="button" class="btn btn-outline-secondary" wire:click="$set('showAnimalModal', false)">Anuluj</button>
                        <button type="submit" class="btn btn-primary">Zapisz</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($showDeleteModal)
        <div class="livewire-modal-backdrop">
            <div class="livewire-modal">
                <h2 class="h5">Potwierdz usuniecie</h2>
                <p class="text-muted">Usuniecie zwierzecia usunie rowniez karmienia, wazenia, wylinki, notatki i zdjecia.</p>
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-outline-secondary" wire:click="$set('showDeleteModal', false)">Anuluj</button>
                    <button class="btn btn-danger" wire:click="deleteAnimal">Usun</button>
                </div>
            </div>
        </div>
    @endif

    @if($showImportModal)
        <div class="livewire-modal-backdrop">
            <div class="livewire-modal">
                <h2 class="h5 mb-3">Import zwierzecia z API</h2>
                <form wire:submit="importAnimal" class="vstack gap-2">
                    <div>
                        <label class="form-label">secret_tag</label>
                        <input type="text" class="form-control @error('importSecretTag') is-invalid @enderror" wire:model="importSecretTag">
                        @error('importSecretTag') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-2">
                        <button type="button" class="btn btn-outline-secondary" wire:click="$set('showImportModal', false)">Anuluj</button>
                        <button type="submit" class="btn btn-primary">Importuj</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
