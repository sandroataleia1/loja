<?php

declare(strict_types=1);

use App\Core\Auth\Models\User;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use Illuminate\Support\Facades\Hash;

describe('Login', function (): void {

    it('authenticates with valid credentials', function (): void {
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->forTenant($tenant)->create([
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['token', 'user' => ['uuid', 'name', 'email']],
            ])
            ->assertJsonPath('success', true);
    });

    it('rejects invalid password', function (): void {
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->forTenant($tenant)->create();

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false);
    });

    it('rejects inactive user', function (): void {
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->forTenant($tenant)->inactive()->create([
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false);
    });

    it('validates required fields', function (): void {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    });

    it('validates email format', function (): void {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'not-an-email',
            'password' => 'secret123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

});

describe('Logout', function (): void {

    it('revokes current token', function (): void {
        $this->actingAsTenantUser();

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertNoContent();
    });

    it('requires authentication', function (): void {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertUnauthorized();
    });

});

describe('Me', function (): void {

    it('returns authenticated user data', function (): void {
        $this->actingAsTenantUser();

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['user' => ['uuid', 'name', 'email', 'is_active'], 'tenant', 'memberships'],
            ]);
    });

    it('requires authentication', function (): void {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    });

});
