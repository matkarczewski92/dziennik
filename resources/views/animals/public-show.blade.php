<x-layouts.public>
    @php
        $cover = $profile->identity['cover_photo_url'] ?? null;
    @endphp

    <div class="container-fluid px-0 animal-life-page">
        <section
            class="card border-0 shadow-sm mb-3 animal-hero"
            @if($cover)
                style="--hero-bg-image: url('{{ e($cover) }}');"
            @endif
        >
            <div class="card-body position-relative">
                <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                    <div>
                        <h1 class="h3 mb-1">{{ $profile->identity['name'] ?? 'Profil zwierzecia' }}</h1>
                        <div class="text-muted small mb-2">
                            {{ $profile->identity['species'] ?: 'Brak gatunku' }} | Data klucia: {{ $profile->identity['hatch_date'] ?: 'brak' }}
                        </div>
                        <div class="small">{{ $genotypeSummary }}</div>
                    </div>
                    <span class="badge text-bg-info">Profil publiczny (tylko do odczytu)</span>
                </div>
            </div>
        </section>

        <section class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 mb-3">Galeria</h2>
                <div class="gallery-strip">
                    @php
                        $publicPhotos = $animal->photos->sortByDesc('id')->values();
                    @endphp
                    @forelse($publicPhotos as $index => $photo)
                        <button type="button" class="gallery-thumb-btn" onclick="window.publicGallery?.open({{ $index }})">
                            <img src="{{ $photo->url }}" alt="Zdjecie w galerii" class="gallery-thumb">
                        </button>
                    @empty
                        <div class="text-muted small">Brak zdjec w galerii.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <div class="row g-3 align-items-start">
            <div class="col-12 col-xl-3">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Dane podstawowe</h2>
                        <dl class="animal-details-grid mb-0">
                            <dt>Plec</dt>
                            <dd>
                                @if(($profile->identity['sex'] ?? 'unknown') === 'male')
                                    Samiec
                                @elseif(($profile->identity['sex'] ?? 'unknown') === 'female')
                                    Samica
                                @else
                                    Nieznana
                                @endif
                            </dd>
                            <dt>Data wyklucia</dt>
                            <dd>{{ $profile->identity['hatch_date'] ?: '-' }}</dd>
                            <dt>Data zakupu</dt>
                            <dd>{{ $profile->identity['acquired_at'] ?: '-' }}</dd>
                            <dt>Waga biezaca</dt>
                            <dd>{{ $profile->identity['current_weight_grams'] !== null ? number_format((float) $profile->identity['current_weight_grams'], 2, ',', ' ') . ' g' : '-' }}</dd>
                            <dt>Interwal karmien</dt>
                            <dd>{{ $profile->identity['feeding_interval_days'] ?: '-' }} dni</dd>
                        </dl>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Genetyka</h2>
                        <div class="vstack gap-2">
                            @forelse($profile->genotypeChips as $chip)
                                @php
                                    $type = strtolower((string) ($chip['type'] ?? ''));
                                    $prefix = $type === 'h' ? 'het. ' : ($type === 'p' ? 'ph ' : '');
                                @endphp
                                <div class="genotype-chip-row">
                                    <div class="small fw-semibold">{{ $prefix }}{{ $chip['name'] ?? 'Gen' }}</div>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">Brak wpisanych genow.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Karmienia</h2>
                        <div class="vstack gap-2">
                            @forelse($profile->feedingsByYear as $year => $feedings)
                                <div class="border rounded overflow-hidden">
                                    <details @if((string) $year === now()->format('Y')) open @endif>
                                        <summary class="px-3 py-2 feeding-year-toggle">{{ $year }}</summary>
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead>
                                                <tr>
                                                    <th>Data</th>
                                                    <th>Pokarm</th>
                                                    <th>Ilosc</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($feedings as $feeding)
                                                    <tr>
                                                        <td>{{ $feeding->fed_at?->format('Y-m-d') }}</td>
                                                        <td>{{ $feeding->feed?->name ?? $feeding->prey }}</td>
                                                        <td>{{ $feeding->quantity }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </details>
                                </div>
                            @empty
                                <p class="text-muted mb-0">Brak karmien.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Waga</h2>

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
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($weights as $weight)
                                        <tr>
                                            <td>{{ $weight->measured_at?->format('Y-m-d') }}</td>
                                            <td>{{ number_format((float) $weight->weight_grams, 2, ',', ' ') }} g</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-muted">Brak wpisow wazenia.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($weights->hasPages())
                            <div class="mt-3">{{ $weights->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-3">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Wylinki</h2>
                        <div class="vstack gap-2">
                            @forelse($profile->sheds as $shed)
                                <div class="border rounded p-2">
                                    <div class="small fw-semibold">{{ $shed->shed_at?->format('Y-m-d') }}</div>
                                    <div class="small text-muted">{{ $shed->quality ?: 'brak oceny' }}</div>
                                    @if($shed->notes)
                                        <div class="small">{{ $shed->notes }}</div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-muted small mb-0">Brak wpisow o wylinkach.</p>
                            @endforelse
                        </div>
                        @if($profile->sheds->hasPages())
                            <div class="mt-3">{{ $profile->sheds->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(($publicPhotos ?? collect())->isNotEmpty())
        <div id="public-gallery-modal" class="livewire-modal-backdrop d-none">
            <div class="livewire-modal">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="h6 mb-0">Podglad zdjecia</h3>
                    <button class="btn-close" type="button" onclick="window.publicGallery?.close()"></button>
                </div>

                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="window.publicGallery?.prev()">&larr; Poprzednie</button>
                    <button class="btn btn-outline-danger btn-sm" type="button" onclick="window.publicGallery?.close()">Wylacz podglad</button>
                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="window.publicGallery?.next()">Nastepne &rarr;</button>
                </div>

                <div class="text-center mb-2">
                    <img id="public-gallery-image" src="" alt="Podglad zdjecia" class="img-fluid rounded">
                </div>
            </div>
        </div>

        <script>
            (function () {
                const photos = @json($publicPhotos->pluck('url')->values()->all());
                const modal = document.getElementById('public-gallery-modal');
                const image = document.getElementById('public-gallery-image');
                let activeIndex = 0;

                if (!modal || !image || !photos.length) {
                    return;
                }

                const render = () => {
                    image.src = photos[activeIndex] || '';
                };

                window.publicGallery = {
                    open(index) {
                        activeIndex = Number.isInteger(index) ? index : 0;
                        if (activeIndex < 0) activeIndex = 0;
                        if (activeIndex >= photos.length) activeIndex = photos.length - 1;
                        render();
                        modal.classList.remove('d-none');
                    },
                    close() {
                        modal.classList.add('d-none');
                    },
                    prev() {
                        activeIndex = (activeIndex - 1 + photos.length) % photos.length;
                        render();
                    },
                    next() {
                        activeIndex = (activeIndex + 1) % photos.length;
                        render();
                    }
                };
            })();
        </script>
    @endif
</x-layouts.public>
