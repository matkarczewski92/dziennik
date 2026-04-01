<?php

namespace Tests\Feature;

use App\Livewire\Admin\FeedsPanel;
use App\Models\Animal;
use App\Models\Feed;
use App\Models\Feeding;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminFeedsPanelManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_feed_from_admin_panel(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin);

        Livewire::test(FeedsPanel::class)
            ->set('name', 'Mysz 40-50g')
            ->set('feedingInterval', '12')
            ->set('amount', '18')
            ->set('lastPrice', '3.40')
            ->call('save')
            ->assertHasNoErrors();

        $feed = Feed::query()->where('name', 'Mysz 40-50g')->first();

        $this->assertNotNull($feed);
        $this->assertSame(12, $feed->feeding_interval);
        $this->assertSame(18, $feed->amount);
        $this->assertSame('3.40', (string) $feed->last_price);
    }

    public function test_admin_can_edit_feed_from_admin_panel(): void
    {
        $admin = $this->createAdmin();
        $feed = Feed::factory()->create([
            'name' => 'Stara karma',
            'feeding_interval' => 7,
            'amount' => 12,
            'last_price' => 2.50,
        ]);

        $this->actingAs($admin);

        Livewire::test(FeedsPanel::class)
            ->call('startEdit', $feed->id)
            ->set('name', 'Nowa karma')
            ->set('feedingInterval', '9')
            ->set('amount', '20')
            ->set('lastPrice', '4,25')
            ->call('save')
            ->assertHasNoErrors();

        $feed->refresh();

        $this->assertSame('Nowa karma', $feed->name);
        $this->assertSame(9, $feed->feeding_interval);
        $this->assertSame(20, $feed->amount);
        $this->assertSame('4.25', (string) $feed->last_price);
    }

    public function test_admin_can_delete_feed_and_existing_feedings_keep_history(): void
    {
        $admin = $this->createAdmin();
        $owner = User::factory()->create();
        $feed = Feed::factory()->create([
            'name' => 'Szczur 50g',
        ]);
        $animal = Animal::factory()->for($owner)->create();
        $feeding = Feeding::factory()
            ->for($owner)
            ->for($animal)
            ->for($feed)
            ->create([
                'prey' => $feed->name,
            ]);

        $this->actingAs($admin);

        Livewire::test(FeedsPanel::class)
            ->call('confirmDelete', $feed->id)
            ->call('deleteFeed')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('feeds', ['id' => $feed->id]);
        $this->assertDatabaseHas('feedings', [
            'id' => $feeding->id,
            'feed_id' => null,
            'prey' => 'Szczur 50g',
        ]);
    }

    protected function createAdmin(): User
    {
        $adminRole = Role::findOrCreate('admin');

        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        return $admin;
    }
}
