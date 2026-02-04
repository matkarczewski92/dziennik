<?php

namespace App\Observers;

use App\Models\AnimalOffer;
use App\Services\Animal\AnimalEventProjector;

class AnimalOfferObserver
{
    public function created(AnimalOffer $offer): void
    {
        app(AnimalEventProjector::class)->projectOffer($offer);
    }

    public function updated(AnimalOffer $offer): void
    {
        app(AnimalEventProjector::class)->projectOffer($offer);
    }

    public function deleted(AnimalOffer $offer): void
    {
        app(AnimalEventProjector::class)->removeOffer($offer);
    }
}

