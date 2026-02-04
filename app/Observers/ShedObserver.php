<?php

namespace App\Observers;

use App\Models\Shed;
use App\Services\Animal\AnimalEventProjector;

class ShedObserver
{
    public function created(Shed $shed): void
    {
        app(AnimalEventProjector::class)->projectShed($shed);
    }

    public function updated(Shed $shed): void
    {
        app(AnimalEventProjector::class)->projectShed($shed);
    }

    public function deleted(Shed $shed): void
    {
        app(AnimalEventProjector::class)->removeShed($shed);
    }
}

