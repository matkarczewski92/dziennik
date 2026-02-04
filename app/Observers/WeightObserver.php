<?php

namespace App\Observers;

use App\Models\Weight;
use App\Services\Animal\AnimalEventProjector;

class WeightObserver
{
    public function created(Weight $weight): void
    {
        app(AnimalEventProjector::class)->projectWeight($weight);
    }

    public function updated(Weight $weight): void
    {
        app(AnimalEventProjector::class)->projectWeight($weight);
    }

    public function deleted(Weight $weight): void
    {
        app(AnimalEventProjector::class)->removeWeight($weight);
    }
}

