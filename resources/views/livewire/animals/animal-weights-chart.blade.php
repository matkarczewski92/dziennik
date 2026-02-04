<div class="card border-0 shadow-sm mb-3" id="weights-panel">
    <div class="card-body">
        <h2 class="h6 mb-3">Waga</h2>

        <form wire:submit="save" class="row g-2 mb-3">
            <div class="col-12 col-md-4">
                <input type="date" class="form-control @error('form.measured_at') is-invalid @enderror" wire:model="form.measured_at">
                @error('form.measured_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12 col-md-4">
                <input type="number" step="0.01" class="form-control @error('form.weight_grams') is-invalid @enderror" wire:model="form.weight_grams" placeholder="Waga (g)">
                @error('form.weight_grams') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12 col-md-4 d-grid">
                <button class="btn btn-primary" type="submit">{{ $editingId ? 'Zapisz zmiany' : 'Dodaj wazenie' }}</button>
            </div>
            @if($editingId)
                <div class="col-12">
                    <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="cancelEdit">Anuluj edycje</button>
                </div>
            @endif
        </form>

        @if($chart['has_data'])
            <div class="weight-chart-wrap mb-3">
                <svg viewBox="0 0 102 56" class="weight-chart">
                    @foreach($chart['x_ticks'] as $tick)
                        <line x1="{{ $tick['x'] }}" y1="{{ $chart['bounds']['top'] }}" x2="{{ $tick['x'] }}" y2="{{ $chart['bounds']['bottom'] }}" class="weight-grid-line"></line>
                    @endforeach
                    @foreach($chart['y_ticks'] as $tick)
                        <line x1="{{ $chart['bounds']['left'] }}" y1="{{ $tick['y'] }}" x2="{{ $chart['bounds']['right'] }}" y2="{{ $tick['y'] }}" class="weight-grid-line"></line>
                        <text x="1.5" y="{{ $tick['y'] + 1 }}" class="weight-axis-label">{{ $tick['label'] }}</text>
                    @endforeach
                    <path d="{{ $chart['area'] }}" class="weight-area"></path>
                    <path d="{{ $chart['line'] }}" class="weight-line"></path>
                    @foreach($chart['points'] as $point)
                        <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="0.45" class="weight-point"></circle>
                    @endforeach

                    @foreach($chart['x_ticks'] as $tick)
                        <text x="{{ $tick['x'] }}" y="54.5" text-anchor="end" transform="rotate(-36 {{ $tick['x'] }} 54.5)" class="weight-axis-label-x">{{ $tick['label'] }}</text>
                    @endforeach
                </svg>
                <div class="d-flex justify-content-center small text-muted mt-1">
                    <span><span class="legend-weight-line"></span> Waga</span>
                </div>
            </div>
        @else
            <p class="text-muted small">Brak danych do wykresu.</p>
        @endif

        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Waga</th>
                    <th class="text-end">Akcje</th>
                </tr>
                </thead>
                <tbody>
                @forelse($weights as $weight)
                    <tr @class(['table-active' => $editingId === $weight->id])>
                        <td>{{ $weight->measured_at?->format('Y-m-d') }}</td>
                        <td>{{ number_format((float) $weight->weight_grams, 2, ',', ' ') }} g</td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <button class="btn btn-sm btn-outline-secondary" wire:click="startEdit({{ $weight->id }})">Edytuj</button>
                                <button class="btn btn-sm btn-outline-danger" wire:click="delete({{ $weight->id }})">Usun</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-muted">Brak wpisow wazenia.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @include('components.pagination.inline-controls', ['paginator' => $weights])
    </div>
</div>
