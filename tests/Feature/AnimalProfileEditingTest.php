<?php

namespace Tests\Feature;

use App\Livewire\Animals\Profile;
use App\Models\Animal;
use App\Models\Feed;
use App\Models\Feeding;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnimalProfileEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_edit_existing_feeding_from_profile(): void
    {
        $user = User::factory()->create();
        $animal = Animal::factory()->for($user)->create();
        $oldFeed = Feed::factory()->create(['name' => 'Mysz 10-16g']);
        $newFeed = Feed::factory()->create(['name' => 'Szczur 5-9g']);

        $feeding = Feeding::factory()->create([
            'user_id' => $user->id,
            'animal_id' => $animal->id,
            'feed_id' => $oldFeed->id,
            'prey' => $oldFeed->name,
            'fed_at' => '2026-01-01',
            'quantity' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(Profile::class, ['animal' => $animal])
            ->call('startEditFeeding', $feeding->id)
            ->set('feedingForm.fed_at', '2026-01-02')
            ->set('feedingForm.feed_id', $newFeed->id)
            ->set('feedingForm.quantity', 2)
            ->set('feedingForm.prey_weight_grams', 18.5)
            ->set('feedingForm.notes', 'Po aktualizacji')
            ->call('addFeeding')
            ->assertHasNoErrors();

        $this->assertSame(1, Feeding::query()->where('animal_id', $animal->id)->count());
        $this->assertDatabaseHas('feedings', [
            'id' => $feeding->id,
            'feed_id' => $newFeed->id,
            'prey' => $newFeed->name,
            'fed_at' => '2026-01-02',
            'quantity' => 2,
            'notes' => 'Po aktualizacji',
        ]);
    }

    public function test_user_can_edit_existing_note_from_profile(): void
    {
        $user = User::factory()->create();
        $animal = Animal::factory()->for($user)->create();

        $note = Note::factory()->create([
            'user_id' => $user->id,
            'animal_id' => $animal->id,
            'body' => 'Stara notatka',
            'is_pinned' => false,
        ]);

        Livewire::actingAs($user)
            ->test(Profile::class, ['animal' => $animal])
            ->call('startEditNote', $note->id)
            ->set('noteForm.body', 'Nowa tresc notatki')
            ->set('noteForm.is_pinned', true)
            ->call('addNote')
            ->assertHasNoErrors();

        $this->assertSame(1, Note::query()->where('animal_id', $animal->id)->count());
        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'body' => 'Nowa tresc notatki',
            'is_pinned' => 1,
        ]);
    }
}
