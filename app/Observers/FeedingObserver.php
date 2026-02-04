<?php

namespace App\Observers;

use App\Models\Feeding;
use App\Services\Animal\AnimalEventProjector;

class FeedingObserver
{
    public function created(Feeding $feeding): void
    {
        app(AnimalEventProjector::class)->projectFeeding($feeding);
    }

    public function updated(Feeding $feeding): void
    {
        app(AnimalEventProjector::class)->projectFeeding($feeding);
    }

    public function deleted(Feeding $feeding): void
    {
        app(AnimalEventProjector::class)->removeFeeding($feeding);
    }
}

