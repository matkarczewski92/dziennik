<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Dashboard</h1>
        <button class="btn btn-outline-primary btn-sm" wire:click="refreshData">Odswiez</button>
    </div>

    @if($globalMessage)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-2">Komunikat hodowcy</h2>
                <p class="mb-0">{{ $globalMessage }}</p>
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Karmienia: dzisiaj</h2>
                    @forelse($feedingToday as $item)
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <a href="{{ route('animals.show', $item['animal']->id) }}">{{ $item['animal']->name }}</a>
                            <span class="badge text-bg-danger">dzis</span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Brak karmien na dzisiaj.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Karmienia: jutro</h2>
                    @forelse($feedingTomorrow as $item)
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <a href="{{ route('animals.show', $item['animal']->id) }}">{{ $item['animal']->name }}</a>
                            <span class="badge text-bg-warning">jutro</span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Brak karmien na jutro.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6">Przypomnienia o wazeniu (30 dni)</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Zwierze</th>
                                    <th>Gatunek</th>
                                    <th>Ostatnia waga</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($weightReminders as $animal)
                                    <tr>
                                        <td><a href="{{ route('animals.show', $animal->id) }}">{{ $animal->name }}</a></td>
                                        <td>{{ $animal->species?->name ?: '-' }}</td>
                                        <td>{{ $animal->current_weight_grams ? number_format($animal->current_weight_grams, 2, ',', ' ') . ' g' : 'brak danych' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted">Brak zaleglych pomiarow.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
