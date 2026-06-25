<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\TenantSettings;
use App\Core\Tenancy\Models\Tenant;
use App\Modules\Settings\Services\TenantSettingsCache;
use Illuminate\Support\Facades\Cache;

// TestCase e RefreshDatabase configurados globalmente em tests/Pest.php

beforeEach(function (): void {
    Cache::flush();
});

// ── get / put ─────────────────────────────────────────────────────────────────

test('get() retorna null quando cache está vazio', function (): void {
    $this->actingAsTenantUser();
    $cache = app(TenantSettingsCache::class);

    expect($cache->get($this->tenant->uuid, 'commercial'))->toBeNull();
});

test('put() e get() funcionam corretamente', function (): void {
    $this->actingAsTenantUser();
    $cache = app(TenantSettingsCache::class);

    $data = ['freight_policy' => 'fixed', 'freight_fixed_cents' => 1500];
    $cache->put($this->tenant->uuid, 'commercial', $data);

    expect($cache->get($this->tenant->uuid, 'commercial'))->toBe($data);
});

test('cache populado ao chamar getSection()', function (): void {
    $this->actingAsTenantUser();

    $settings = TenantSettings::forTenant($this->tenant->uuid);
    $settings->getSection('financial'); // popula cache

    $cache = app(TenantSettingsCache::class);
    expect($cache->get($this->tenant->uuid, 'financial'))->toBeArray();
});

test('updateSection() invalida cache da seção alterada', function (): void {
    $this->actingAsTenantUser();
    $cache    = app(TenantSettingsCache::class);
    $settings = TenantSettings::forTenant($this->tenant->uuid);

    // Popula cache
    $settings->getSection('commercial');
    expect($cache->get($this->tenant->uuid, 'commercial'))->not->toBeNull();

    // Atualiza → deve invalidar
    $settings->updateSection('commercial', ['quote_validity_days' => 45]);

    expect($cache->get($this->tenant->uuid, 'commercial'))->toBeNull();
});

test('forget() remove apenas a seção especificada', function (): void {
    $this->actingAsTenantUser();
    $cache = app(TenantSettingsCache::class);

    $cache->put($this->tenant->uuid, 'commercial', ['a' => 1]);
    $cache->put($this->tenant->uuid, 'financial', ['b' => 2]);

    $cache->forget($this->tenant->uuid, 'commercial');

    expect($cache->get($this->tenant->uuid, 'commercial'))->toBeNull()
        ->and($cache->get($this->tenant->uuid, 'financial'))->toBeArray();
});

test('forgetAll() remove todas as seções do tenant', function (): void {
    $this->actingAsTenantUser();
    $cache = app(TenantSettingsCache::class);

    foreach (TenantSettingsCache::SECTIONS as $section) {
        $cache->put($this->tenant->uuid, $section, ['x' => 1]);
    }

    $cache->forgetAll($this->tenant->uuid);

    foreach (TenantSettingsCache::SECTIONS as $section) {
        expect($cache->get($this->tenant->uuid, $section))->toBeNull();
    }
});

test('Tenant A não lê cache do Tenant B', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $cache = app(TenantSettingsCache::class);

    $cache->put($tenantA->uuid, 'commercial', ['quote_validity_days' => 10]);
    $cache->put($tenantB->uuid, 'commercial', ['quote_validity_days' => 99]);

    expect($cache->get($tenantA->uuid, 'commercial')['quote_validity_days'])->toBe(10)
        ->and($cache->get($tenantB->uuid, 'commercial')['quote_validity_days'])->toBe(99);
});
