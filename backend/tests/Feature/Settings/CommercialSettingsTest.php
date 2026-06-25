<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\TenantSettings;
use Illuminate\Support\Facades\Cache;

// TestCase e RefreshDatabase configurados globalmente em tests/Pest.php

beforeEach(function (): void {
    Cache::flush();
});

// ── Política de frete ─────────────────────────────────────────────────────────

test('freight_policy fixed sem freight_fixed_cents retorna 422', function (): void {
    $this->actingAsTenantUser();

    $response = $this->putJson('/api/v1/system-settings/commercial', [
        'freight_policy' => 'fixed',
        // freight_fixed_cents ausente
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['freight_fixed_cents']);
});

test('freight_policy free_above_amount sem free_above_cents retorna 422', function (): void {
    $this->actingAsTenantUser();

    $response = $this->putJson('/api/v1/system-settings/commercial', [
        'freight_policy' => 'free_above_amount',
        // free_above_cents ausente
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['free_above_cents']);
});

test('freight_policy fixed com freight_fixed_cents válido retorna 200', function (): void {
    $this->actingAsTenantUser();

    $response = $this->putJson('/api/v1/system-settings/commercial', [
        'freight_policy'      => 'fixed',
        'freight_fixed_cents' => 1500,
    ]);

    $response->assertOk();

    $settings = TenantSettings::forTenant($this->tenant->uuid);
    $section  = $settings->getSection('commercial');

    expect($section['freight_policy'])->toBe('fixed')
        ->and($section['freight_fixed_cents'])->toBe(1500);
});

// ── min_order_by_channel ──────────────────────────────────────────────────────

test('min_order_by_channel persiste corretamente por canal', function (): void {
    $this->actingAsTenantUser();

    $response = $this->putJson('/api/v1/system-settings/commercial', [
        'min_order_by_channel' => [
            'counter'        => 0,
            'delivery'       => 8000,
            'representative' => 20000,
        ],
    ]);

    $response->assertOk();

    $settings = TenantSettings::forTenant($this->tenant->uuid);
    $section  = $settings->getSection('commercial');

    expect($section['min_order_by_channel']['counter'])->toBe(0)
        ->and($section['min_order_by_channel']['delivery'])->toBe(8000)
        ->and($section['min_order_by_channel']['representative'])->toBe(20000);
});

// ── Regra cruzada safety_stock / min_stock_alert ──────────────────────────────

test('safety_stock maior que min_stock_alert retorna 422', function (): void {
    $this->actingAsTenantUser();

    $response = $this->putJson('/api/v1/system-settings/inventory', [
        'min_stock_alert' => 10,
        'safety_stock'    => 20, // maior que min_stock_alert → inválido
    ]);

    $response->assertStatus(422);
});

test('safety_stock menor ou igual a min_stock_alert retorna 200', function (): void {
    $this->actingAsTenantUser();

    $response = $this->putJson('/api/v1/system-settings/inventory', [
        'min_stock_alert' => 10,
        'safety_stock'    => 5,
    ]);

    $response->assertOk();
});

// ── Campos financeiros (F1-F3) ────────────────────────────────────────────────

test('currency deve ter exatamente 3 caracteres', function (): void {
    $this->actingAsTenantUser();

    $this->putJson('/api/v1/system-settings/financial', ['currency' => 'BRLX'])
        ->assertStatus(422);

    $this->putJson('/api/v1/system-settings/financial', ['currency' => 'BRL'])
        ->assertOk();
});

test('locale só aceita pt_BR ou en_US', function (): void {
    $this->actingAsTenantUser();

    $this->putJson('/api/v1/system-settings/financial', ['locale' => 'fr_FR'])
        ->assertStatus(422);

    $this->putJson('/api/v1/system-settings/financial', ['locale' => 'pt_BR'])
        ->assertOk();
});

test('decimal_separator só aceita comma ou dot', function (): void {
    $this->actingAsTenantUser();

    $this->putJson('/api/v1/system-settings/financial', ['decimal_separator' => 'semicolon'])
        ->assertStatus(422);

    $this->putJson('/api/v1/system-settings/financial', ['decimal_separator' => 'dot'])
        ->assertOk();
});

// ── Logística (F9) ────────────────────────────────────────────────────────────

test('delivery_radius_km deve ser entre 1 e 500', function (): void {
    $this->actingAsTenantUser();

    $this->putJson('/api/v1/system-settings/logistics', ['delivery_radius_km' => 0])
        ->assertStatus(422);

    $this->putJson('/api/v1/system-settings/logistics', ['delivery_radius_km' => 501])
        ->assertStatus(422);

    $this->putJson('/api/v1/system-settings/logistics', ['delivery_radius_km' => 50])
        ->assertOk();
});

test('default_delivery_days deve ser entre 0 e 90', function (): void {
    $this->actingAsTenantUser();

    $this->putJson('/api/v1/system-settings/logistics', ['default_delivery_days' => 91])
        ->assertStatus(422);

    $this->putJson('/api/v1/system-settings/logistics', ['default_delivery_days' => 7])
        ->assertOk();
});
