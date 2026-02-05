<?php

namespace Tests\Feature;

use App\Livewire\Animals\AnimalHero;
use App\Models\Animal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicAnimalProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profile_page_is_available_for_shared_animal(): void
    {
        $user = User::factory()->create();
        $animal = Animal::factory()->for($user)->create([
            'name' => 'Mamba',
            'public_profile_enabled' => true,
            'public_profile_token' => 'public-token-12345',
        ]);

        $this->get(route('animals.public', ['token' => $animal->public_profile_token]))
            ->assertOk()
            ->assertSee('Profil publiczny')
            ->assertSee('Mamba')
            ->assertDontSee('Edytuj dane')
            ->assertDontSee('Usun');
    }

    public function test_public_profile_page_returns_404_when_sharing_is_disabled(): void
    {
        $user = User::factory()->create();
        $animal = Animal::factory()->for($user)->create([
            'public_profile_enabled' => false,
            'public_profile_token' => 'disabled-token-12345',
        ]);

        $this->get(route('animals.public', ['token' => $animal->public_profile_token]))
            ->assertNotFound();
    }

    public function test_owner_can_enable_public_profile_from_animal_hero(): void
    {
        $user = User::factory()->create();
        $animal = Animal::factory()->for($user)->create([
            'public_profile_enabled' => false,
            'public_profile_token' => null,
        ]);

        $this->actingAs($user);

        Livewire::test(AnimalHero::class, [
            'animalId' => $animal->id,
            'identity' => [
                'name' => $animal->name,
                'species' => '',
                'hatch_date' => null,
            ],
            'genotypeChips' => [],
        ])->call('openShareModal');

        $animal->refresh();

        $this->assertTrue($animal->public_profile_enabled);
        $this->assertNotNull($animal->public_profile_token);
    }
}
