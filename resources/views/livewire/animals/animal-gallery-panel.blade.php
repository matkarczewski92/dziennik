<section class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h6 mb-0">Galeria</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-light btn-sm" type="button" wire:click="openManageModal">Edycja galerii</button>
                <button class="btn btn-primary btn-sm" type="button" wire:click="openManageModal">Dodaj zdjecie</button>
            </div>
        </div>

        <div class="gallery-strip">
            @forelse($photos as $photo)
                <button type="button" class="gallery-thumb-btn" wire:click="openPhotoModal({{ $photo->id }})">
                    <img src="{{ $photo->url }}" alt="Zdjecie w galerii" class="gallery-thumb">
                </button>
            @empty
                <div class="text-muted small">Brak zdjec w galerii.</div>
            @endforelse
        </div>
    </div>

    @if($showManageModal)
        <div class="livewire-modal-backdrop">
            <div class="livewire-modal">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Edycja galerii</h3>
                    <button class="btn-close" type="button" wire:click="closeManageModal"></button>
                </div>

                <form
                    wire:submit="uploadPhoto"
                    class="vstack gap-2 mb-3"
                    x-data="{ isUploading: false, progress: 0 }"
                    x-on:livewire-upload-start="isUploading = true"
                    x-on:livewire-upload-finish="isUploading = false; progress = 0"
                    x-on:livewire-upload-error="isUploading = false"
                    x-on:livewire-upload-progress="progress = $event.detail.progress"
                >
                    <input type="file" class="form-control @error('photoUpload') is-invalid @enderror" wire:model="photoUpload" accept="image/*">
                    @error('photoUpload') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    <small class="text-muted">Max 25MB. Zdjecie zostanie przeskalowane do max 1920x1080.</small>
                    <input type="date" class="form-control @error('photo_taken_at') is-invalid @enderror" wire:model="photo_taken_at">
                    @error('photo_taken_at') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    <textarea class="form-control @error('photo_notes') is-invalid @enderror" rows="3" wire:model="photo_notes" placeholder="Notatka do zdjecia"></textarea>
                    @error('photo_notes') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    <div class="progress" x-show="isUploading">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" x-bind:style="'width: ' + progress + '%'" x-text="progress + '%'"></div>
                    </div>
                    <button class="btn btn-primary align-self-start" type="submit">Dodaj zdjecie</button>
                </form>

                <div class="row g-2">
                    @forelse($photos as $photo)
                        <div class="col-6 col-md-4">
                            <div class="border rounded overflow-hidden">
                                <button type="button" class="btn p-0 border-0 w-100 text-start" wire:click="openPhotoModal({{ $photo->id }})">
                                    <img src="{{ $photo->url }}" alt="Zdjecie w edycji galerii" class="img-fluid">
                                </button>
                                <div class="p-2">
                                    <div class="small text-muted mb-2">Dodano: {{ $photo->created_at?->format('Y-m-d H:i') ?: '-' }}</div>
                                    @if((int) $coverPhotoId === (int) $photo->id)
                                        <div class="small text-success mb-2">Zdjecie glowne</div>
                                    @else
                                        <button class="btn btn-sm btn-outline-primary w-100 mb-2" wire:click="setAsCover({{ $photo->id }})">Ustaw jako glowne</button>
                                    @endif
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
    @endif

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
                    <img src="{{ $activePhoto->url }}" alt="Podglad zdjecia" class="img-fluid rounded">
                </div>
                <div class="small text-muted">Data dodania: {{ $activePhoto->created_at?->format('Y-m-d H:i') ?: '-' }}</div>
            </div>
        </div>
    @endif
</section>
