<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_impersonate_user_and_return_back(): void
    {
        $adminRole = Role::findOrCreate('admin');
        $userRole = Role::findOrCreate('user');

        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $user = User::factory()->create();
        $user->assignRole($userRole);

        $this->actingAs($admin)
            ->post(route('admin.impersonate', $user))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame($admin->id, session('impersonator_id'));

        $this->post(route('impersonation.leave'))
            ->assertRedirect(route('admin.users'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('impersonator_id'));
    }
}

