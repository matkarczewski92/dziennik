<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Feed;
use App\Models\Feeding;
use App\Models\User;
use App\Services\FeedingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FeedsDictionaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_feeds_dictionary_is_seeded_from_legacy_dump(): void
    {
        $this->seed();

        $this->assertSame(12, Feed::count());
        $this->assertDatabaseHas('feeds', [
            'id' => 9,
            'name' => 'Odmowa przyjecia pokarmu',
        ]);
    }

    public function test_feeding_service_stores_feed_id_reference(): void
    {
        $this->seed();

        Role::findOrCreate('user');
        $user = User::factory()->create();
        $user->assignRole('user');

        $animal = Animal::factory()->for($user)->create();
        $feed = Feed::query()->findOrFail(1);

        $feeding = app(FeedingService::class)->create($user, $animal, [
            'feed_id' => $feed->id,
            'fed_at' => now()->toDateString(),
            'prey_weight_grams' => 10.5,
            'quantity' => 1,
            'notes' => 'Test',
        ]);

        $this->assertInstanceOf(Feeding::class, $feeding);
        $this->assertSame($feed->id, $feeding->feed_id);
        $this->assertSame($feed->name, $feeding->prey);
    }
}

