<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_email_and_password(): void
    {
        $response = $this->post('/register', [
            'name' => 'Nowy Uzytkownik',
            'email' => 'nowy@example.com',
            'password' => 'Secret12345!',
            'password_confirmation' => 'Secret12345!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'nowy@example.com']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        Role::findOrCreate('user');

        $user = User::factory()->create([
            'email' => 'logowanie@example.com',
            'password' => Hash::make('Secret12345!'),
        ]);
        $user->assignRole('user');

        $response = $this->post('/login', [
            'email' => 'logowanie@example.com',
            'password' => 'Secret12345!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }
}

