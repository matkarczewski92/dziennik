<?php

namespace App\Observers;

use App\Models\Photo;
use App\Services\Animal\AnimalEventProjector;

class PhotoObserver
{
    public function created(Photo $photo): void
    {
        app(AnimalEventProjector::class)->projectPhoto($photo);
    }

    public function updated(Photo $photo): void
    {
        app(AnimalEventProjector::class)->projectPhoto($photo);
    }

    public function deleted(Photo $photo): void
    {
        app(AnimalEventProjector::class)->removePhoto($photo);
    }
}

