<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructionPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_instruction_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('instruction'))
            ->assertOk()
            ->assertSee('Instrukcja')
            ->assertSee('Dodanie weza');
    }
}
