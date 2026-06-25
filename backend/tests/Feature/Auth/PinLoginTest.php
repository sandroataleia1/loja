<?php

declare(strict_types=1);

use App\Core\Auth\Models\TenantUser;
use App\Core\Auth\Models\User;
use App\Core\Tenancy\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;

// TestCase e RefreshDatabase configurados globalmente em tests/Pest.php

beforeEach(function (): void {
    RateLimiter::clear('pin-login');
});

// ── Helpers ──────────────────────────────────────────────────────────────────

function makeUserWithPin(Tenant $tenant, string $pin = '1234'): User
{
    $user = User::factory()->forTenant($tenant)->create([
        'pin'       => hash('sha256', config('app.key') . $pin),
        'is_active' => true,
    ]);

    TenantUser::factory()->create([
        'tenant_id' => $tenant->uuid,
        'user_id'   => $user->uuid,
        'is_active' => true,
    ]);

    return $user;
}

// ── Tests ─────────────────────────────────────────────────────────────────────

test('PIN correto retorna token Sanctum', function (): void {
    $tenant = Tenant::factory()->create();
    makeUserWithPin($tenant, '1234');

    $response = $this->postJson('/api/v1/pin-login', [
        'tenant_id'   => $tenant->uuid,
        'pin'         => '1234',
        'device_name' => 'PDV-001',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['token', 'user']])
        ->assertJsonPath('success', true);
});

test('PIN inválido retorna 401', function (): void {
    $tenant = Tenant::factory()->create();
    makeUserWithPin($tenant, '1234');

    $response = $this->postJson('/api/v1/pin-login', [
        'tenant_id'   => $tenant->uuid,
        'pin'         => '9999',
        'device_name' => 'PDV-001',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('success', false);
});

test('conta bloqueada retorna 423', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = makeUserWithPin($tenant, '1234');

    // Bloqueia a conta
    $user->update([
        'locked_until'       => Carbon::now()->addMinutes(15),
        'failed_login_count' => 5,
    ]);

    $response = $this->postJson('/api/v1/pin-login', [
        'tenant_id'   => $tenant->uuid,
        'pin'         => '1234',
        'device_name' => 'PDV-001',
    ]);

    $response->assertStatus(423);
});

test('11ª tentativa retorna 429 (throttle)', function (): void {
    $tenant = Tenant::factory()->create();
    makeUserWithPin($tenant, '1234');

    // Simula throttle esgotado (10 tentativas no limite configurado)
    $key = 'pin-login-' . request()->ip() . '|' . $tenant->uuid;
    RateLimiter::hit($key, 5 * 60);

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit($key, 5 * 60);
    }

    $response = $this->postJson('/api/v1/pin-login', [
        'tenant_id'   => $tenant->uuid,
        'pin'         => '9999',
        'device_name' => 'PDV-001',
    ]);

    $response->assertStatus(429);
});

test('PIN não é exposto na resposta', function (): void {
    $tenant = Tenant::factory()->create();
    makeUserWithPin($tenant, '1234');

    $response = $this->postJson('/api/v1/pin-login', [
        'tenant_id'   => $tenant->uuid,
        'pin'         => '1234',
        'device_name' => 'PDV-001',
    ]);

    $response->assertOk();
    $content = $response->json();
    $encoded = json_encode($content);

    // Nenhum campo pin deve aparecer no payload
    expect($encoded)->not->toContain('"pin"');
});

test('usuário de outro tenant não consegue logar', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    makeUserWithPin($tenantA, '1234'); // usuário é do tenantA

    $response = $this->postJson('/api/v1/pin-login', [
        'tenant_id'   => $tenantB->uuid, // tenta com tenantB
        'pin'         => '1234',
        'device_name' => 'PDV-001',
    ]);

    $response->assertStatus(401);
});
