<div class="container-fluid px-0" wire:init="loadOffers">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Aktualna oferta</h1>
        <button class="btn btn-outline-primary btn-sm" wire:click="loadOffers">Odswiez</button>
    </div>

    @if(! $readyToLoad)
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 text-muted">
                    <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
                    <span>Ladowanie aktualnej oferty...</span>
                </div>
            </div>
        </div>
    @elseif($errorMessage)
        <div class="alert alert-danger">
            Nie udalo sie pobrac aktualnej oferty. {{ $errorMessage }}
        </div>
    @elseif($offersByType === [])
        <div class="alert alert-secondary">Brak aktywnych ofert.</div>
    @else
        @foreach($offersByType as $typeName => $offers)
            <section class="mb-4">
                <h2 class="h5 mb-3">{{ $typeName }}</h2>
                <div class="row g-3">
                    @foreach($offers as $offer)
                        @php
                            $photoUrl = $offer['main_photo_url'] ?: asset('images/placeholder-animal.svg');
                            $birthDate = $offer['date_of_birth'] ?: '-';
                            $priceLabel = $offer['price'] !== null
                                ? number_format((float) $offer['price'], 2, ',', ' ') . ' zl'
                                : 'Cena do ustalenia';
                        @endphp
                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                            <article class="card border-0 shadow-sm h-100 offer-card">
                                <img src="{{ $photoUrl }}" class="card-img-top offer-card-image" alt="{{ $offer['name'] }}">
                                <div class="card-body d-flex flex-column">
                                    <h3 class="h5 offer-title mb-2">{{ $offer['name'] }}</h3>
                                    <div class="text-muted small mb-3">
                                        {{ $offer['sex_label'] }} · ur. {{ $birthDate }}
                                    </div>

                                    @if($offer['has_reservation'])
                                        <div class="mb-3">
                                            <span class="badge text-bg-warning">Rezerwacja</span>
                                        </div>
                                    @endif

                                    <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top border-secondary-subtle">
                                        <div class="fw-semibold">{{ $priceLabel }}</div>
                                        @if($offer['public_profile_url'])
                                            <a class="btn btn-outline-light btn-sm" href="{{ $offer['public_profile_url'] }}" target="_blank" rel="noopener noreferrer">Profil</a>
                                        @else
                                            <span class="btn btn-secondary btn-sm disabled">Profil niedostepny</span>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    @endif
</div>
