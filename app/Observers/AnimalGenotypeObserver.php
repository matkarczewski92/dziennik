<?php

namespace App\Observers;

use App\Models\AnimalGenotype;
use App\Services\Animal\AnimalEventProjector;

class AnimalGenotypeObserver
{
    public function created(AnimalGenotype $genotype): void
    {
        app(AnimalEventProjector::class)->projectGenotype($genotype);
    }

    public function updated(AnimalGenotype $genotype): void
    {
        app(AnimalEventProjector::class)->projectGenotype($genotype);
    }

    public function deleted(AnimalGenotype $genotype): void
    {
        app(AnimalEventProjector::class)->removeGenotype($genotype);
    }
}

