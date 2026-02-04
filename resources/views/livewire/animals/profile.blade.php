<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center">
                <div>
                    <h1 class="h4 mb-1">{{ $animal->name }}</h1>
                    <div class="text-muted small">{{ $animal->species?->name ?: 'brak gatunku' }} | {{ $animal->morph ?: 'brak morph' }}</div>
                </div>
                <a href="{{ route('animals.index') }}" class="btn btn-outline-secondary btn-sm">Powrot do listy</a>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <button class="btn btn-sm {{ $activeTab === 'genetics' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setTab('genetics')">Genetyka</button>
        <button class="btn btn-sm {{ $activeTab === 'feedings' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setTab('feedings')">Karmienia</button>
        <button class="btn btn-sm {{ $activeTab === 'weights' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setTab('weights')">Wazenia</button>
        <button class="btn btn-sm {{ $activeTab === 'sheds' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setTab('sheds')">Wylinki</button>
        <button class="btn btn-sm {{ $activeTab === 'notes' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setTab('notes')">Notatnik</button>
        <button class="btn btn-sm {{ $activeTab === 'photos' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setTab('photos')">Galeria</button>
    </div>

    @if($activeTab === 'genetics')
        <div class="row g-3">
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 mb-3">{{ $editingGenotypeId ? 'Edytuj genotyp' : 'Dodaj genotyp' }}</h2>
                        <form wire:submit="addGenotype" class="vstack gap-2">
                            <input
                                type="text"
                                list="gene-dictionary"
                                class="form-control @error('genotypeForm.gene_name') is-invalid @enderror"
                                wire:model="genotypeForm.gene_name"
                                placeholder="Wpisz nazwe genu (np. Amel, Anery, Tessera)"
                            >
                            <datalist id="gene-dictionary">
                                @foreach($genotypeCategories as $category)
                                    <option value="{{ $category->name }}">{{ $category->gene_code }}</option>
                                @endforeach
                            </datalist>
                            @error('genotypeForm.gene_name') <div class="invalid-feedback">{{ $message }}</div> @enderror

                            <select class="form-select @error('genotypeForm.type') is-invalid @enderror" wire:model="genotypeForm.type">
                                <option value="v">v - homozygota</option>
                                <option value="h">h - heterozygota</option>
                                <option value="p">p - poshet</option>
                            </select>
                            @error('genotypeForm.type') <div class="invalid-feedback">{{ $message }}</div> @enderror

                            <div class="d-flex gap-2">
                                <button class="btn btn-primary" type="submit">{{ $editingGenotypeId ? 'Zapisz zmiany' : 'Dodaj genotyp' }}</button>
                                @if($editingGenotypeId)
                                    <button class="btn btn-outline-secondary" type="button" wire:click="cancelEditGenotype">Anuluj edycje</button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Genotyp zwierzecia</h2>
                        @forelse($genotypes as $genotype)
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <div>
                                    <div>{{ $genotype->genotypeCategory?->name }} ({{ $genotype->genotypeCategory?->gene_code }})</div>
                                    <div class="small text-muted">Typ: {{ $genotype->type }}</div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-secondary" wire:click="startEditGenotype({{ $genotype->id }})">Edytuj</button>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="deleteGenotype({{ $genotype->id }})">Usun</button>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Brak przypisanych genotypow.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($activeTab === 'feedings')
        <div class="row g-3">
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 mb-3">{{ $editingFeedingId ? 'Edytuj karmienie' : 'Dodaj karmienie' }}</h2>
                        <form wire:submit="addFeeding" class="vstack gap-2">
                            <label class="form-label small text-muted mb-0">Data karmienia</label>
                            <input type="date" class="form-control @error('feedingForm.fed_at') is-invalid @enderror" wire:model="feedingForm.fed_at">
                            <label class="form-label small text-muted mb-0">Rodzaj karmy</label>
                            <select class="form-select @error('feedingForm.feed_id') is-invalid @enderror" wire:model="feedingForm.feed_id">
                                <option value="">-- wybierz rodzaj karmy --</option>
                                @foreach($feedOptions as $feedOption)
                                    <option value="{{ $feedOption->id }}">{{ $feedOption->name }}</option>
                                @endforeach
                            </select>
                            <label class="form-label small text-muted mb-0">Ilosc</label>
                            <input type="number" class="form-control @error('feedingForm.quantity') is-invalid @enderror" wire:model="feedingForm.quantity" placeholder="Ilosc">
                            <label class="form-label small text-muted mb-0">Notatka</label>
                            <textarea class="form-control @error('feedingForm.notes') is-invalid @enderror" rows="3" wire:model="feedingForm.notes" placeholder="Notatka"></textarea>
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary" type="submit">{{ $editingFeedingId ? 'Zapisz zmiany' : 'Zapisz karmienie' }}</button>
                                @if($editingFeedingId)
                                    <button class="btn btn-outline-secondary" type="button" wire:click="cancelEditFeeding">Anuluj edycje</button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Historia karmien</h2>
                        @forelse($feedings as $feeding)
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <div>
                                    <div>{{ $feeding->fed_at?->format('Y-m-d') }} - {{ $feeding->feed?->name ?? $feeding->prey }}</div>
                                    <div class="small text-muted">ilosc: {{ $feeding->quantity }}</div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-secondary" wire:click="startEditFeeding({{ $feeding->id }})">Edytuj</button>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="deleteFeeding({{ $feeding->id }})">Usun</button>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Brak wpisow karmien.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($activeTab === 'weights')
        <div class="row g-3">
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 mb-3">{{ $editingWeightId ? 'Edytuj wazenie' : 'Dodaj wazenie' }}</h2>
                        <form wire:submit="addWeight" class="vstack gap-2">
                            <input type="date" class="form-control @error('weightForm.measured_at') is-invalid @enderror" wire:model="weightForm.measured_at">
                            <input type="number" step="0.01" class="form-control @error('weightForm.weight_grams') is-invalid @enderror" wire:model="weightForm.weight_grams" placeholder="Waga (g)">
                            <textarea class="form-control @error('weightForm.notes') is-invalid @enderror" rows="3" wire:model="weightForm.notes" placeholder="Notatka"></textarea>
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary" type="submit">{{ $editingWeightId ? 'Zapisz zmiany' : 'Zapisz wazenie' }}</button>
                                @if($editingWeightId)
                                    <button class="btn btn-outline-secondary" type="button" wire:click="cancelEditWeight">Anuluj edycje</button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Historia wazen</h2>
                        @forelse($weights as $weight)
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <div>
                                    <div>{{ $weight->measured_at?->format('Y-m-d') }} — {{ number_format($weight->weight_grams, 2, ',', ' ') }} g</div>
                                    <div class="small text-muted">{{ $weight->notes ?: '-' }}</div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-secondary" wire:click="startEditWeight({{ $weight->id }})">Edytuj</button>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="deleteWeight({{ $weight->id }})">Usun</button>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Brak wpisow wazen.</p>
                        @endforelse
                        @if($weights instanceof \Illuminate\Contracts\Pagination\Paginator && $weights->hasPages())
                            <div class="mt-3">{{ $weights->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($activeTab === 'sheds')
        <div class="row g-3">
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 mb-3">{{ $editingShedId ? 'Edytuj wylinke' : 'Dodaj wylinke' }}</h2>
                        <form wire:submit="addShed" class="vstack gap-2">
                            <input type="date" class="form-control @error('shedForm.shed_at') is-invalid @enderror" wire:model="shedForm.shed_at">
                            <input type="text" class="form-control @error('shedForm.quality') is-invalid @enderror" wire:model="shedForm.quality" placeholder="Ocena (np. pelna)">
                            <textarea class="form-control @error('shedForm.notes') is-invalid @enderror" rows="3" wire:model="shedForm.notes" placeholder="Notatka"></textarea>
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary" type="submit">{{ $editingShedId ? 'Zapisz zmiany' : 'Zapisz wylinke' }}</button>
                                @if($editingShedId)
                                    <button class="btn btn-outline-secondary" type="button" wire:click="cancelEditShed">Anuluj edycje</button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Historia wylinek</h2>
                        @forelse($sheds as $shed)
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <div>
                                    <div>{{ $shed->shed_at?->format('Y-m-d') }} — {{ $shed->quality ?: 'brak oceny' }}</div>
                                    <div class="small text-muted">{{ $shed->notes ?: '-' }}</div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-secondary" wire:click="startEditShed({{ $shed->id }})">Edytuj</button>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="deleteShed({{ $shed->id }})">Usun</button>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Brak wpisow wylinek.</p>
                        @endforelse
                        @if($sheds instanceof \Illuminate\Contracts\Pagination\Paginator && $sheds->hasPages())
                            <div class="mt-3">{{ $sheds->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($activeTab === 'notes')
        <div class="row g-3">
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 mb-3">{{ $editingNoteId ? 'Edytuj notatke' : 'Dodaj notatke' }}</h2>
                        <form wire:submit="addNote" class="vstack gap-2">
                            <textarea class="form-control @error('noteForm.body') is-invalid @enderror" wire:model="noteForm.body" rows="5" placeholder="Tresc notatki"></textarea>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model="noteForm.is_pinned" id="isPinned">
                                <label class="form-check-label" for="isPinned">Przypnij notatke</label>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary" type="submit">{{ $editingNoteId ? 'Zapisz zmiany' : 'Zapisz notatke' }}</button>
                                @if($editingNoteId)
                                    <button class="btn btn-outline-secondary" type="button" wire:click="cancelEditNote">Anuluj edycje</button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Notatki</h2>
                        @forelse($notes as $note)
                            <div class="border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        @if($note->is_pinned)
                                            <span class="badge text-bg-warning mb-1">Przypieta</span>
                                        @endif
                                        <p class="mb-1">{{ $note->body }}</p>
                                        <small class="text-muted">{{ $note->created_at?->format('Y-m-d H:i') }}</small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-secondary" wire:click="startEditNote({{ $note->id }})">Edytuj</button>
                                        <button class="btn btn-sm btn-outline-danger" wire:click="deleteNote({{ $note->id }})">Usun</button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Brak notatek.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($activeTab === 'photos')
        <div class="row g-3">
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Dodaj zdjecie</h2>
                        <form
                            wire:submit="uploadPhoto"
                            class="vstack gap-2"
                            x-data="{ isUploading: false, progress: 0 }"
                            x-on:livewire-upload-start="isUploading = true"
                            x-on:livewire-upload-finish="isUploading = false; progress = 0"
                            x-on:livewire-upload-error="isUploading = false"
                            x-on:livewire-upload-progress="progress = $event.detail.progress"
                        >
                            <input type="file" class="form-control @error('photoUpload') is-invalid @enderror" wire:model="photoUpload" accept="image/*">
                            <small class="text-muted">Max 25MB. Zdjecie zostanie przeskalowane do max 1920x1080 i zapisane w formacie JPG/PNG/WebP (bez oryginalu).</small>
                            <input type="date" class="form-control @error('photo_taken_at') is-invalid @enderror" wire:model="photo_taken_at">
                            <textarea class="form-control @error('photo_notes') is-invalid @enderror" rows="3" wire:model="photo_notes" placeholder="Notatka do zdjecia"></textarea>
                            <div class="progress" x-show="isUploading">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" x-bind:style="'width: ' + progress + '%'" x-text="progress + '%'"></div>
                            </div>
                            <button class="btn btn-primary" type="submit">Zapisz zdjecie</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Galeria</h2>
                        <div class="row g-2">
                            @forelse($photos as $photo)
                                <div class="col-6 col-md-4">
                                    <div class="border rounded overflow-hidden">
                                        <button
                                            type="button"
                                            class="btn p-0 border-0 w-100 text-start"
                                            wire:click="openPhotoModal({{ $photo->id }})"
                                        >
                                            <img src="{{ $photo->url }}" alt="Zdjecie {{ $animal->name }}" class="img-fluid">
                                        </button>
                                        <div class="p-2">
                                            <div class="small text-muted">Dodano: {{ $photo->created_at?->format('Y-m-d H:i') ?: '-' }}</div>
                                            <div class="small text-muted mb-2">{{ $photo->size_kb }} KB</div>
                                            <button class="btn btn-sm btn-outline-danger w-100" wire:click="deletePhoto({{ $photo->id }})">Usun</button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <p class="text-muted mb-0">Brak zdjec.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($showPhotoModal && $activePhoto)
            <div class="livewire-modal-backdrop">
                <div class="livewire-modal">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h3 class="h6 mb-0">Podglad zdjecia</h3>
                        <button class="btn-close" type="button" wire:click="closePhotoModal"></button>
                    </div>

                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <button class="btn btn-outline-secondary btn-sm" wire:click="showPreviousPhoto">&larr; Poprzednie</button>
                        <button class="btn btn-outline-secondary btn-sm" wire:click="showNextPhoto">Nastepne &rarr;</button>
                    </div>

                    <div class="text-center mb-2">
                        <img src="{{ $activePhoto->url }}" alt="Podglad zdjecia {{ $animal->name }}" class="img-fluid rounded">
                    </div>
                    <div class="small text-muted">
                        Data dodania: {{ $activePhoto->created_at?->format('Y-m-d H:i') ?: '-' }}
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
