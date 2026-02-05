<?php

namespace Tests\Feature;

use App\Livewire\Admin\UsersPanel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUsersPanelManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_edit_user_data_from_users_panel(): void
    {
        $adminRole = Role::findOrCreate('admin');
        $userRole = Role::findOrCreate('user');

        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $target = User::factory()->create([
            'name' => 'Stara nazwa',
            'email' => 'stary@example.com',
        ]);
        $target->assignRole($userRole);

        $this->actingAs($admin);

        Livewire::test(UsersPanel::class)
            ->call('startEdit', $target->id)
            ->set('editName', 'Nowa nazwa')
            ->set('editEmail', 'nowy@example.com')
            ->call('saveEdit');

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'Nowa nazwa',
            'email' => 'nowy@example.com',
        ]);
    }

    public function test_admin_can_delete_user_from_users_panel(): void
    {
        $adminRole = Role::findOrCreate('admin');
        $userRole = Role::findOrCreate('user');

        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $target = User::factory()->create();
        $target->assignRole($userRole);

        $this->actingAs($admin);

        Livewire::test(UsersPanel::class)
            ->call('confirmDelete', $target->id)
            ->call('deleteUser');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }
}
