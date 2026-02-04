<div class="card border-0 shadow-sm mb-3" id="feedings-panel">
    <div class="card-body">
        <h2 class="h6 mb-3">Karmienia</h2>

        <form wire:submit="save" class="row g-2 mb-3">
            <div class="col-12 col-md-3">
                <label class="form-label small text-secondary mb-1">Data karmienia</label>
                <input type="date" class="form-control @error('form.fed_at') is-invalid @enderror" wire:model="form.fed_at">
                @error('form.fed_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small text-secondary mb-1">Rodzaj karmy</label>
                <select class="form-select @error('form.feed_id') is-invalid @enderror" wire:model="form.feed_id">
                    <option value="">-- wybierz karme --</option>
                    @foreach($feedOptions as $feed)
                        <option value="{{ $feed->id }}">{{ $feed->name }}</option>
                    @endforeach
                </select>
                @error('form.feed_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small text-secondary mb-1">Ilosc</label>
                <input type="number" class="form-control @error('form.quantity') is-invalid @enderror" wire:model="form.quantity" min="1" placeholder="Ilosc">
                @error('form.quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-secondary mb-1 d-block">&nbsp;</label>
                <button class="btn btn-primary w-100" type="submit">{{ $editingId ? 'Zapisz zmiany' : 'Dodaj karmienie' }}</button>
            </div>
            <div class="col-12 d-flex flex-wrap gap-2">
                @if($editingId)
                    <button class="btn btn-outline-secondary" type="button" wire:click="cancelEdit">Anuluj edycje</button>
                @endif
            </div>
        </form>

        <div class="vstack gap-2">
            @forelse($feedingsByYear as $year => $feedings)
                @php
                    $isExpanded = (bool) ($expandedYears[$year] ?? false);
                @endphp
                <div class="border rounded overflow-hidden">
                    <button type="button" class="btn btn-link text-decoration-none w-100 text-start px-3 py-2 feeding-year-toggle" wire:click="toggleYear('{{ $year }}')">
                        <span class="me-2">{{ $isExpanded ? '▾' : '▸' }}</span>{{ $year }}
                    </button>
                    @if($isExpanded)
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Pokarm</th>
                                    <th>Ilosc</th>
                                    <th class="text-end">Akcje</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($feedings as $feeding)
                                    <tr @class(['table-active' => $editingId === $feeding->id])>
                                        <td>{{ $feeding->fed_at?->format('Y-m-d') }}</td>
                                        <td>{{ $feeding->feed?->name ?? $feeding->prey }}</td>
                                        <td>{{ $feeding->quantity }}</td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <button class="btn btn-sm btn-outline-secondary" wire:click="startEdit({{ $feeding->id }})">Edytuj</button>
                                                <button class="btn btn-sm btn-outline-danger" wire:click="delete({{ $feeding->id }})">Usun</button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-muted mb-0">Brak karmien.</p>
            @endforelse
        </div>
    </div>
</div>
