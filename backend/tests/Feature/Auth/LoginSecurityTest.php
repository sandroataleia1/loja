<?php

declare(strict_types=1);

use App\Core\Auth\Models\User;
use App\Core\Tenancy\Models\Tenant;
use Illuminate\Support\Carbon;

// TestCase e RefreshDatabase configurados globalmente em tests/Pest.php

// ── Helpers ──────────────────────────────────────────────────────────────────

function makeActiveUser(Tenant $tenant, string $password = 'Senha@123'): User
{
    return User::factory()->forTenant($tenant)->create([
        'password'  => \Illuminate\Support\Facades\Hash::make($password),
        'is_active' => true,
    ]);
}

function attemptLogin(Tests\TestCase $test, string $email, string $password, string $tenantId): \Illuminate\Testing\TestResponse
{
    return $test->postJson('/api/v1/login', [
        'email'       => $email,
        'password'    => $password,
        'tenant_id'   => $tenantId,
        'device_name' => 'test',
    ]);
}

// ── Tests ─────────────────────────────────────────────────────────────────────

test('5 tentativas falhas bloqueiam a conta', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = makeActiveUser($tenant);

    for ($i = 0; $i < 5; $i++) {
        attemptLogin($this, $user->email, 'senha-errada', $tenant->uuid);
    }

    $user->refresh();

    expect($user->failed_login_count)->toBeGreaterThanOrEqual(5)
        ->and($user->locked_until)->not->toBeNull()
        ->and($user->locked_until->isFuture())->toBeTrue();
});

test('6ª tentativa com senha correta ainda é bloqueada', function (): void {
    $tenant   = Tenant::factory()->create();
    $password = 'Senha@123';
    $user     = makeActiveUser($tenant, $password);

    // 5 falhas — bloqueiam
    for ($i = 0; $i < 5; $i++) {
        attemptLogin($this, $user->email, 'errada', $tenant->uuid);
    }

    // 6ª com senha certa
    $response = attemptLogin($this, $user->email, $password, $tenant->uuid);

    $response->assertStatus(403); // BusinessException traduz para 403 ou 422 conforme handler
});

test('após locked_until expirar, login com senha correta funciona', function (): void {
    $tenant   = Tenant::factory()->create();
    $password = 'Senha@123';
    $user     = makeActiveUser($tenant, $password);

    // Define bloqueio expirado
    $user->update([
        'failed_login_count' => 5,
        'locked_until'       => Carbon::now()->subMinutes(1),
    ]);

    $response = attemptLogin($this, $user->email, $password, $tenant->uuid);

    $response->assertOk();
});

test('login bem-sucedido reseta failed_login_count', function (): void {
    $tenant   = Tenant::factory()->create();
    $password = 'Senha@123';
    $user     = makeActiveUser($tenant, $password);

    // Algumas falhas antes
    $user->update(['failed_login_count' => 3]);

    attemptLogin($this, $user->email, $password, $tenant->uuid);

    $user->refresh();

    expect($user->failed_login_count)->toBe(0)
        ->and($user->locked_until)->toBeNull();
});
