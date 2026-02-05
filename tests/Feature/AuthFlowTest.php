<?php

namespace Tests\Feature;

use App\Notifications\PolishResetPasswordNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_bypass_secret_tag(): void
    {
        $response = $this->post('/register', [
            'name' => 'Nowy Uzytkownik',
            'email' => 'nowy@example.com',
            'password' => '12345',
            'password_confirmation' => '12345',
            'secret_tag' => 'MAKSSNAKEST',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'nowy@example.com']);
        $this->assertDatabaseCount('animals', 0);
        Http::assertNothingSent();
    }

    public function test_user_can_register_and_import_animal_by_secret_tag(): void
    {
        config([
            'hodowla.base_url' => 'https://example.test/api',
            'hodowla.token' => 'test-token',
        ]);

        Http::fake([
            'https://example.test/api/animals/*' => Http::response([
                'data' => [
                    'animal' => [
                        'id' => 321,
                        'name' => 'Mamba',
                        'sex' => 2,
                        'secret_tag' => 'QHPU4R',
                        'animal_type' => ['id' => 77, 'name' => 'Python regius'],
                    ],
                    'genetics' => [],
                    'feedings' => [],
                    'weights' => [],
                    'sheds' => [],
                    'litters' => [],
                    'gallery' => [],
                ],
            ], 200),
        ]);

        $response = $this->post('/register', [
            'name' => 'Nowy Uzytkownik',
            'email' => 'import@example.com',
            'password' => '12345',
            'password_confirmation' => '12345',
            'secret_tag' => 'QHPU4R',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'import@example.com')->firstOrFail();
        $this->assertDatabaseHas('animals', [
            'user_id' => $user->id,
            'secret_tag' => 'QHPU4R',
            'name' => 'Mamba',
            'imported_from_api' => 1,
        ]);
    }

    public function test_register_fails_when_secret_tag_not_found_in_api(): void
    {
        config([
            'hodowla.base_url' => 'https://example.test/api',
            'hodowla.token' => 'test-token',
        ]);

        Http::fake([
            'https://example.test/api/animals/*' => Http::response(['message' => 'Not found'], 404),
        ]);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Nowy Uzytkownik',
            'email' => 'brakzwierzecia@example.com',
            'password' => '12345',
            'password_confirmation' => '12345',
            'secret_tag' => 'QHPU4R',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['secret_tag']);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'brakzwierzecia@example.com']);
    }

    public function test_register_fails_for_password_shorter_than_five_characters(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Nowy Uzytkownik',
            'email' => 'za-krotkie@example.com',
            'password' => '1234',
            'password_confirmation' => '1234',
            'secret_tag' => 'MAKSSNAKEST',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'za-krotkie@example.com']);
    }

    public function test_user_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('12345'),
        ]);

        $response = $this->post('/forgot-password', [
            'email' => 'reset@example.com',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status');
        Notification::assertSentTo($user, PolishResetPasswordNotification::class);
    }

    public function test_user_can_reset_password_from_link(): void
    {
        $user = User::factory()->create([
            'email' => 'zmiana@example.com',
            'password' => Hash::make('12345'),
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'zmiana@example.com',
            'password' => '54321',
            'password_confirmation' => '54321',
        ]);

        $response->assertRedirect('/login');
        $this->assertTrue(Hash::check('54321', $user->fresh()->password));
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
