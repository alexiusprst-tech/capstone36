<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_via_api(): void
    {
        $response = $this->postJson(route('api.auth.register'), [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
            'token',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
        ]);
    }

    public function test_user_can_login_via_api(): void
    {
        $user = User::factory()->create([
            'email' => 'siti@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson(route('api.auth.login'), [
            'email' => 'siti@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
            'token',
        ]);
    }
}
