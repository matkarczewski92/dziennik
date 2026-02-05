@php
    $cover = $identity['cover_photo_url'] ?? null;
@endphp

<div>
    <section
        class="card border-0 shadow-sm mb-3 animal-hero"
        @if($cover)
            style="--hero-bg-image: url('{{ e($cover) }}');"
        @endif
    >
        <div class="card-body position-relative">
            <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                <div>
                    <h1 class="h3 mb-1">{{ $identity['name'] ?? 'Profil zwierzecia' }}</h1>
                    <div class="text-muted small mb-2">
                        {{ $identity['species'] ?? 'Brak gatunku' }} | Data klucia: {{ $identity['hatch_date'] ?: 'brak' }}
                    </div>
                    <div class="small">{{ $this->genotypeSummary() }}</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('animals.index') }}" class="btn btn-outline-light btn-sm">Powrot do listy</a>
                    <button type="button" class="btn btn-outline-info btn-sm" wire:click="openShareModal">Udostepnij profil</button>
                    <button type="button" class="btn btn-primary btn-sm" wire:click="openEditModal">Edytuj dane</button>
                </div>
            </div>
        </div>
    </section>

    @if($showShareModal)
        <div class="livewire-modal-backdrop">
            <div class="livewire-modal">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Udostepnij profil</h3>
                    <button class="btn-close" type="button" wire:click="$set('showShareModal', false)"></button>
                </div>

                <p class="small text-muted mb-2">
                    Link prowadzi do publicznego profilu tylko do odczytu (bez mozliwosci edycji i usuwania danych).
                </p>
                <label class="form-label small text-muted mb-1">Link publiczny</label>
                <div class="input-group mb-3">
                    <input id="public-profile-link-{{ $this->getId() }}" type="text" class="form-control" readonly value="{{ $publicProfileUrl ?? '' }}">
                    <button
                        class="btn btn-outline-secondary"
                        type="button"
                        onclick="navigator.clipboard?.writeText(document.getElementById('public-profile-link-{{ $this->getId() }}')?.value || '')"
                    >
                        Kopiuj
                    </button>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" wire:click="$set('showShareModal', false)">Zamknij</button>
                    <button type="button" class="btn btn-outline-danger" wire:click="disablePublicProfile">Wylacz udostepnianie</button>
                </div>
            </div>
        </div>
    @endif

    @if($showEditModal)
        <div class="livewire-modal-backdrop">
            <div class="livewire-modal">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Edycja danych podstawowych</h3>
                    <button class="btn-close" type="button" wire:click="closeEditModal"></button>
                </div>

                <form wire:submit="saveBasicData" class="row g-2">
                    <div class="col-12">
                        <label class="form-label">Nazwa</label>
                        <input type="text" class="form-control @error('form.name') is-invalid @enderror" wire:model="form.name">
                        @error('form.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Gatunek</label>
                        <select class="form-select @error('form.species_id') is-invalid @enderror" wire:model="form.species_id">
                            <option value="">-- wybierz gatunek --</option>
                            @foreach($speciesOptions as $species)
                                <option value="{{ $species->id }}">{{ $species->name }}</option>
                            @endforeach
                        </select>
                        @error('form.species_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Plec</label>
                        <select class="form-select @error('form.sex') is-invalid @enderror" wire:model="form.sex">
                            <option value="unknown">Nieznana</option>
                            <option value="male">Samiec</option>
                            <option value="female">Samica</option>
                        </select>
                        @error('form.sex') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Data wyklucia</label>
                        <input type="date" class="form-control @error('form.hatch_date') is-invalid @enderror" wire:model="form.hatch_date">
                        @error('form.hatch_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Data zakupu</label>
                        <input type="date" class="form-control @error('form.acquired_at') is-invalid @enderror" wire:model="form.acquired_at">
                        @error('form.acquired_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Waga biezaca (g)</label>
                        <input type="number" step="0.01" class="form-control @error('form.current_weight_grams') is-invalid @enderror" wire:model="form.current_weight_grams">
                        @error('form.current_weight_grams') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Interwal karmienia (dni)</label>
                        <input type="number" min="1" max="90" class="form-control @error('form.feeding_interval_days') is-invalid @enderror" wire:model="form.feeding_interval_days">
                        @error('form.feeding_interval_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 mt-2">
                        <div class="border rounded p-3">
                            <label class="form-label mb-2">Czy wyrazasz zgode na uzycie zdjec Twojego zwierzaka w mediach spolecznosciowych MaksSnake?</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="social_media_consent_{{ $this->getId() }}" wire:model="form.social_media_consent">
                                <label class="form-check-label" for="social_media_consent_{{ $this->getId() }}">
                                    {{ !empty($form['social_media_consent']) ? 'Tak' : 'Nie' }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeEditModal">Anuluj</button>
                        <button type="submit" class="btn btn-primary">Zapisz</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
