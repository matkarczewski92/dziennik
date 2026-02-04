<?php

namespace Tests\Feature;

use App\Livewire\Animals\Index;
use App\Models\Animal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnimalsOwnerScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_open_other_user_animal_profile(): void
    {
        Role::findOrCreate('user');

        $owner = User::factory()->create();
        $owner->assignRole('user');

        $other = User::factory()->create();
        $other->assignRole('user');

        $animal = Animal::factory()->for($other)->create();

        $this->actingAs($owner)
            ->get(route('animals.show', $animal))
            ->assertForbidden();
    }

    public function test_animals_list_is_scoped_to_owner(): void
    {
        Role::findOrCreate('user');

        $owner = User::factory()->create();
        $owner->assignRole('user');

        $other = User::factory()->create();
        $other->assignRole('user');

        $myAnimal = Animal::factory()->for($owner)->create(['name' => 'Moja Kobra']);
        $otherAnimal = Animal::factory()->for($other)->create(['name' => 'Cudzy Python']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->assertSee($myAnimal->name)
            ->assertDontSee($otherAnimal->name);
    }

    public function test_sidebar_tree_shows_only_owner_animals(): void
    {
        Role::findOrCreate('user');

        $owner = User::factory()->create();
        $owner->assignRole('user');

        $other = User::factory()->create();
        $other->assignRole('user');

        $myAnimal = Animal::factory()->for($owner)->create(['name' => 'Moja Kobra']);
        $otherAnimal = Animal::factory()->for($other)->create(['name' => 'Cudzy Python']);

        $response = $this->actingAs($owner)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Zwierzeta');
        $response->assertSee($myAnimal->name);
        $response->assertDontSee($otherAnimal->name);
        $response->assertSee(route('animals.show', $myAnimal), false);
    }
}
