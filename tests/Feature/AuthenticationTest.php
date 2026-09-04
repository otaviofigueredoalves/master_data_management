<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'secret-password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'email']]]);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    public function test_user_can_login_and_receive_a_token(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create(['password' => 'secret-password']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = $this->seedTenantWithMembers()['admin'];

        $this->actingAsSanctum($user)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('device')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_update_profile_rejects_email_already_in_use_by_another_user(): void
    {
        $fixtures = $this->seedTenantWithMembers();

        $this->actingAsSanctum($fixtures['admin'])
            ->putJson('/api/v1/me', [
                'name' => $fixtures['admin']->name,
                'email' => $fixtures['viewer']->email,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_update_profile_accepts_timezone_and_locale(): void
    {
        $fixtures = $this->seedTenantWithMembers();

        $this->actingAsSanctum($fixtures['admin'])
            ->putJson('/api/v1/me', [
                'name' => $fixtures['admin']->name,
                'email' => $fixtures['admin']->email,
                'timezone' => 'America/Sao_Paulo',
                'locale' => 'pt_BR',
            ])
            ->assertOk()
            ->assertJsonPath('data.timezone', 'America/Sao_Paulo')
            ->assertJsonPath('data.locale', 'pt_BR');
    }
}
