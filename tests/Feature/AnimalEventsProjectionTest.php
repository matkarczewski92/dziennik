<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\AnimalEvent;
use App\Models\AnimalGenotype;
use App\Models\AnimalGenotypeCategory;
use App\Models\AnimalOffer;
use App\Models\AnimalSpecies;
use App\Models\Feed;
use App\Models\Feeding;
use App\Models\Photo;
use App\Models\Shed;
use App\Models\User;
use App\Models\Weight;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnimalEventsProjectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_observer_projects_feeding_lifecycle_to_event_stream(): void
    {
        $user = User::factory()->create();
        $species = AnimalSpecies::query()->create(['name' => 'Pantherophis guttatus']);
        $feed = Feed::query()->create([
            'name' => 'Mouse',
            'feeding_interval' => 14,
            'amount' => 20,
            'last_price' => 8,
        ]);

        $animal = Animal::query()->create([
            'user_id' => $user->id,
            'name' => 'QHPU4R',
            'species_id' => $species->id,
            'sex' => 'male',
            'feeding_interval_days' => 14,
        ]);

        $feeding = Feeding::query()->create([
            'user_id' => $user->id,
            'animal_id' => $animal->id,
            'feed_id' => $feed->id,
            'fed_at' => '2026-02-01',
            'prey' => 'Mouse',
            'quantity' => 1,
        ]);

        $this->assertSame(1, AnimalEvent::query()->where('type', 'feeding')->count());

        $feeding->update(['quantity' => 2]);
        $this->assertSame(1, AnimalEvent::query()->where('type', 'feeding')->count());

        $feeding->delete();
        $this->assertSame(0, AnimalEvent::query()->where('type', 'feeding')->count());
    }

    public function test_rebuild_events_command_reconstructs_historical_stream(): void
    {
        $user = User::factory()->create();
        $species = AnimalSpecies::query()->create(['name' => 'Pantherophis guttatus']);
        $feed = Feed::query()->create([
            'name' => 'Mouse',
            'feeding_interval' => 14,
            'amount' => 20,
            'last_price' => 8,
        ]);

        $animal = Animal::query()->create([
            'user_id' => $user->id,
            'name' => 'History',
            'species_id' => $species->id,
            'sex' => 'female',
            'feeding_interval_days' => 10,
        ]);

        $gene = AnimalGenotypeCategory::query()->create([
            'name' => 'Amel',
            'gene_code' => 'AM',
            'gene_type' => 'r',
        ]);

        Feeding::withoutEvents(function () use ($animal, $user, $feed): void {
            Feeding::query()->create([
                'user_id' => $user->id,
                'animal_id' => $animal->id,
                'feed_id' => $feed->id,
                'fed_at' => '2026-01-18',
                'prey' => 'Mouse',
                'quantity' => 1,
            ]);
        });

        Weight::withoutEvents(function () use ($animal, $user): void {
            Weight::query()->create([
                'user_id' => $user->id,
                'animal_id' => $animal->id,
                'measured_at' => '2026-01-22',
                'weight_grams' => 186.4,
            ]);
        });

        Shed::withoutEvents(function () use ($animal, $user): void {
            Shed::query()->create([
                'user_id' => $user->id,
                'animal_id' => $animal->id,
                'shed_at' => '2026-01-28',
            ]);
        });

        AnimalGenotype::withoutEvents(function () use ($animal, $gene): void {
            AnimalGenotype::query()->create([
                'animal_id' => $animal->id,
                'genotype_id' => $gene->id,
                'type' => 'h',
            ]);
        });

        Photo::withoutEvents(function () use ($animal, $user): void {
            Photo::query()->create([
                'user_id' => $user->id,
                'animal_id' => $animal->id,
                'path' => 'https://example.com/photo.jpg',
            ]);
        });

        AnimalOffer::withoutEvents(function () use ($animal): void {
            AnimalOffer::query()->create([
                'animal_id' => $animal->id,
                'price' => 500,
                'sold_date' => null,
            ]);
        });

        $this->assertSame(0, AnimalEvent::query()->where('animal_id', $animal->id)->count());

        $this->artisan('animals:rebuild-events', ['--animal_id' => $animal->id])
            ->assertExitCode(0);

        $types = AnimalEvent::query()
            ->where('animal_id', $animal->id)
            ->pluck('type')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['feeding', 'genotype', 'offer', 'photo', 'shed', 'weight'], $types);
    }
}

