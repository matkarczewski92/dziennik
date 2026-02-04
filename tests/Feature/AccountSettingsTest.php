<?php

namespace Tests\Feature;

use App\Livewire\Account\Settings;
use App\Models\Animal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_account_settings_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account.settings'))
            ->assertOk()
            ->assertSee('Ustawienia konta');
    }

    public function test_user_can_update_account_name(): void
    {
        $user = User::factory()->create(['name' => 'Stara Nazwa']);

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->set('name', 'Nowa Nazwa')
            ->call('saveName')
            ->assertHasNoErrors()
            ->assertRedirect(route('account.settings'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nowa Nazwa',
        ]);
    }

    public function test_user_can_change_password_with_valid_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Secret12345!'),
        ]);

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->set('passwordForm.current_password', 'Secret12345!')
            ->set('passwordForm.password', 'NoweHaslo123!')
            ->set('passwordForm.password_confirmation', 'NoweHaslo123!')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('account.settings'));

        $user->refresh();
        $this->assertTrue(Hash::check('NoweHaslo123!', $user->password));
    }

    public function test_user_can_delete_account_from_settings_with_confirmation_modal(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Secret12345!'),
        ]);
        $animal = Animal::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->call('openDeleteModal')
            ->set('deleteAccountPassword', 'Secret12345!')
            ->call('deleteAccount')
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('animals', ['id' => $animal->id]);
    }
}
