<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_accounts_for_every_division(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMINISTRASI']));
        $roles = ['MARKETING', 'DIREKTUR', 'ADMINISTRASI', 'WAREHOUSE', 'FINANCE', 'CLIENT'];

        foreach ($roles as $role) {
            $response = $this->postJson(route('api.admin.accounts.store'), [
                'name' => "Pengguna $role",
                'email' => strtolower($role).'@example.com',
                'role' => $role,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            $response->assertCreated()->assertJsonPath('user.role', $role);
            $this->assertDatabaseHas('users', [
                'email' => strtolower($role).'@example.com',
                'role' => $role,
            ]);
        }

        $this->getJson(route('api.admin.accounts.index'))
            ->assertOk()
            ->assertJsonCount(count($roles) + 1, 'users')
            ->assertJsonMissingPath('users.0.password');
    }

    public function test_non_admin_cannot_manage_accounts(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'MARKETING']));

        $this->getJson(route('api.admin.accounts.index'))->assertForbidden();
        $this->postJson(route('api.admin.accounts.store'), [
            'name' => 'Pengguna Baru',
            'email' => 'new-user@example.com',
            'role' => 'FINANCE',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'new-user@example.com']);
    }

    public function test_account_management_requires_authentication(): void
    {
        $this->getJson(route('api.admin.accounts.index'))->assertUnauthorized();
        $this->postJson(route('api.admin.accounts.store'), [])->assertUnauthorized();
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
        $response->assertJsonPath('user.role', 'MARKETING');
    }

    public function test_login_returns_the_saved_role_for_each_division(): void
    {
        foreach (['DIREKTUR', 'ADMINISTRASI', 'WAREHOUSE', 'FINANCE', 'CLIENT'] as $role) {
            $user = User::factory()->create([
                'email' => strtolower($role).'@example.com',
                'password' => bcrypt('password123'),
                'role' => $role,
            ]);

            $this->postJson(route('api.auth.login'), [
                'email' => $user->email,
                'password' => 'password123',
            ])->assertOk()->assertJsonPath('user.role', $role);
        }
    }

    public function test_existing_demo_accounts_receive_their_division_roles(): void
    {
        $rolesByEmail = [
            'marketing@alpharo.co.id' => 'MARKETING',
            'direktur@alpharo.co.id' => 'DIREKTUR',
            'admin@alpharo.co.id' => 'ADMINISTRASI',
            'warehouse@alpharo.co.id' => 'WAREHOUSE',
            'finance@alpharo.co.id' => 'FINANCE',
            'client@alpharo.co.id' => 'CLIENT',
        ];

        foreach ($rolesByEmail as $email => $role) {
            User::factory()->create(['email' => $email, 'role' => 'MARKETING']);
        }

        $migration = require database_path('migrations/2026_11_03_010000_backfill_demo_account_roles.php');
        $migration->up();

        foreach ($rolesByEmail as $email => $role) {
            $this->assertDatabaseHas('users', ['email' => $email, 'role' => $role]);
        }
    }
}
