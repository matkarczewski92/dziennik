<?php

use App\Models\Animal;
use App\Services\Animal\AnimalEventProjector;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('animals:rebuild-events {--animal_id= : Rebuild only one animal by ID}', function (AnimalEventProjector $projector) {
    $animalId = $this->option('animal_id');

    $query = Animal::query()->orderBy('id');
    if (is_numeric($animalId)) {
        $query->where('id', (int) $animalId);
    }

    $count = 0;
    $query->chunkById(100, function ($animals) use ($projector, &$count): void {
        foreach ($animals as $animal) {
            $projector->rebuildForAnimal($animal);
            $count++;
        }
    });

    $this->info("Rebuilt animal_events for {$count} animals.");
})->purpose('Rebuild animal timeline events from historical records');
