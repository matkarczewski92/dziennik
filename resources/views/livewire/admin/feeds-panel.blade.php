<div class="container-fluid px-0">
    <h1 class="h4 mb-3">Karma</h1>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                <div>
                    <h2 class="h6 mb-1">{{ $editingFeedId ? 'Edytuj wpis karmy' : 'Dodaj nowy wpis karmy' }}</h2>
                    <div class="text-muted small">Zarzadzaj slownikiem karmienia dostepnym w aplikacji.</div>
                </div>
                <div class="w-100 w-lg-auto" style="max-width: 320px;">
                    <input type="text" class="form-control" placeholder="Szukaj po nazwie..." wire:model.live.debounce.400ms="search">
                </div>
            </div>

            <form wire:submit="save" class="row g-3">
                <div class="col-12 col-xl-4">
                    <label class="form-label" for="feed-name">Nazwa</label>
                    <input id="feed-name" type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-4 col-xl-2">
                    <label class="form-label" for="feed-interval">Interwal karmienia</label>
                    <div class="input-group">
                        <input id="feed-interval" type="number" min="0" class="form-control @error('feedingInterval') is-invalid @enderror" wire:model="feedingInterval">
                        <span class="input-group-text">dni</span>
                        @error('feedingInterval') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-12 col-md-4 col-xl-2">
                    <label class="form-label" for="feed-amount">Stan</label>
                    <input id="feed-amount" type="number" min="0" class="form-control @error('amount') is-invalid @enderror" wire:model="amount">
                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-4 col-xl-2">
                    <label class="form-label" for="feed-price">Ostatnia cena</label>
                    <div class="input-group">
                        <input id="feed-price" type="text" inputmode="decimal" class="form-control @error('lastPrice') is-invalid @enderror" wire:model="lastPrice" placeholder="0.00">
                        <span class="input-group-text">zl</span>
                    </div>
                    @error('lastPrice') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-xl-2 d-flex align-items-end">
                    <div class="d-flex flex-wrap gap-2 w-100 justify-content-xl-end">
                        @if($editingFeedId)
                            <button type="button" class="btn btn-outline-secondary" wire:click="cancelEdit">Anuluj</button>
                        @endif
                        <button type="submit" class="btn btn-primary">{{ $editingFeedId ? 'Zapisz zmiany' : 'Dodaj wpis' }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nazwa</th>
                        <th>Interwal</th>
                        <th>Powiazane karmienia</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feeds as $feed)
                        <tr @class(['table-active' => $editingFeedId === $feed->id])>
                            <td>{{ $feed->id }}</td>
                            <td>{{ $feed->name }}</td>
                            <td>{{ $feed->feeding_interval }} dni</td>
                            <td>
                                <span class="badge {{ $feed->feedings_count > 0 ? 'text-bg-info' : 'text-bg-secondary' }}">
                                    {{ $feed->feedings_count }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-info" wire:click="startEdit({{ $feed->id }})">Edytuj</button>
                                    <button class="btn btn-outline-danger" wire:click="confirmDelete({{ $feed->id }})">Usun</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Brak wpisow karmy.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $feeds->links('components.pagination.custom') }}
    </div>

    @if($deleteTarget)
        <div class="alert alert-danger mt-4 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <div class="fw-semibold">Usunac wpis "{{ $deleteTarget->name }}"?</div>
                <div class="small mb-0">
                    Powiazane karmienia: {{ $deleteTarget->feedings_count }}.
                    Po usunieciu rekord zniknie ze slownika, a istniejace wpisy karmien zostana odlaczone od `feed_id`.
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-light" wire:click="cancelDelete">Anuluj</button>
                <button class="btn btn-sm btn-danger" wire:click="deleteFeed">Usun wpis</button>
            </div>
        </div>
    @endif
</div>
