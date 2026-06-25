<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\TenantSettings;
use App\Modules\Settings\Models\ConfigHistory;
use Illuminate\Support\Facades\Cache;

// TestCase e RefreshDatabase configurados globalmente em tests/Pest.php

beforeEach(function (): void {
    Cache::flush();
});

// ── updateSection gera histórico ───────────────────────────────────────────────

test('updateSection gera ConfigHistory para cada campo alterado', function (): void {
    $this->actingAsTenantUser();

    $this->putJson('/api/v1/system-settings/commercial', [
        'quote_validity_days'    => 60,
        'default_discount_limit' => 15,
    ]);

    expect(ConfigHistory::where('tenant_id', $this->tenant->uuid)
        ->where('section', 'commercial')
        ->count()
    )->toBe(2); // um por campo alterado
});

test('campos não alterados NÃO geram ConfigHistory', function (): void {
    $this->actingAsTenantUser();

    // Primeiro update para estabelecer baseline
    $this->putJson('/api/v1/system-settings/commercial', [
        'quote_validity_days' => 30, // mesmo valor que o default
    ]);

    expect(ConfigHistory::where('tenant_id', $this->tenant->uuid)->count())->toBe(0);
});

test('histórico captura old_value e new_value corretamente', function (): void {
    $this->actingAsTenantUser();

    // Primeiro: muda para 45
    $this->putJson('/api/v1/system-settings/commercial', ['quote_validity_days' => 45]);
    // Segundo: muda para 60
    $this->putJson('/api/v1/system-settings/commercial', ['quote_validity_days' => 60]);

    $last = ConfigHistory::where('tenant_id', $this->tenant->uuid)
        ->where('key', 'quote_validity_days')
        ->orderByDesc('changed_at')
        ->first();

    expect($last->old_value)->toBe('45')
        ->and($last->new_value)->toBe('60');
});

// ── GET /system-settings/history ──────────────────────────────────────────────

test('GET /history retorna histórico paginado do tenant', function (): void {
    $this->actingAsTenantUser();

    $this->putJson('/api/v1/system-settings/commercial', ['quote_validity_days' => 45]);
    $this->putJson('/api/v1/system-settings/financial', ['tolerance_days' => 5]);

    $response = $this->getJson('/api/v1/system-settings/history');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data', 'meta' => ['total', 'per_page', 'page']]);

    expect($response->json('meta.total'))->toBeGreaterThanOrEqual(2);
});

test('GET /history filtra por seção', function (): void {
    $this->actingAsTenantUser();

    $this->putJson('/api/v1/system-settings/commercial', ['quote_validity_days' => 45]);
    $this->putJson('/api/v1/system-settings/financial', ['tolerance_days' => 5]);

    $response = $this->getJson('/api/v1/system-settings/history?section=commercial');

    $response->assertOk();
    $items = $response->json('data');

    foreach ($items as $item) {
        expect($item['section'])->toBe('commercial');
    }
});

test('GET /history requer permission settings.history', function (): void {
    $this->actingAsTenantUser();
    $this->restrictUserToPermissions(\App\Core\Auth\Enums\PermissionEnum::SettingsView);

    $response = $this->getJson('/api/v1/system-settings/history');

    $response->assertStatus(403);
});
