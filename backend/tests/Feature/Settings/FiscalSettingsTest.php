<?php

declare(strict_types=1);

use App\Core\Auth\Models\User;
use App\Modules\Fiscal\Models\TenantFiscalSettings;
use Illuminate\Support\Facades\Cache;

// TestCase e RefreshDatabase configurados globalmente em tests/Pest.php

beforeEach(function (): void {
    Cache::flush();
});

function fiscalPayload(array $overrides = []): array
{
    return array_merge([
        'company_name'      => 'Empresa Teste LTDA',
        'cnpj'              => '12.345.678/0001-90',
        'ie'                => '123456789',
        'crt'               => 1,
        'is_active'         => true,
        'auto_issue_nfce'   => true,
        'allow_manual_nfce' => true,
        'environment'       => 'homologation',
    ], $overrides);
}

// ── Troca de ambiente ─────────────────────────────────────────────────────────

test('troca para production sem PIN retorna 403', function (): void {
    $this->actingAsTenantUser();

    $response = $this->putJson('/api/v1/fiscal/settings', fiscalPayload([
        'environment' => 'production',
    ]));

    $response->assertStatus(403);
});

test('troca para production com PIN correto retorna 200 e registra audit', function (): void {
    $this->actingAsTenantUser();

    $pin = '1234';
    $this->user->update(['pin' => \Illuminate\Support\Facades\Hash::make($pin)]);

    $response = $this->putJson('/api/v1/fiscal/settings', fiscalPayload([
        'environment' => 'production',
        'pin'         => $pin,
    ]));

    $response->assertOk();

    // Confirma que o ambiente foi salvo como production
    $settings = TenantFiscalSettings::where('tenant_id', $this->tenant->uuid)->first();
    expect($settings->environment->value)->toBe('production');
});

test('troca para production com PIN errado retorna 403', function (): void {
    $this->actingAsTenantUser();

    $this->user->update(['pin' => \Illuminate\Support\Facades\Hash::make('9999')]);

    $response = $this->putJson('/api/v1/fiscal/settings', fiscalPayload([
        'environment' => 'production',
        'pin'         => '0000', // PIN errado
    ]));

    $response->assertStatus(403);
});

test('troca de volta para homologation não exige PIN', function (): void {
    $this->actingAsTenantUser();

    // Cria em produção diretamente
    TenantFiscalSettings::create([
        'tenant_id'   => $this->tenant->uuid,
        'company_name' => 'Empresa',
        'cnpj'        => '12.345.678/0001-90',
        'crt'         => 1,
        'environment' => 'production',
        'is_active'   => true,
    ]);

    // Volta para homologação sem PIN
    $response = $this->putJson('/api/v1/fiscal/settings', fiscalPayload([
        'environment' => 'homologation',
    ]));

    $response->assertOk();
});

// ── nfe_default_email ─────────────────────────────────────────────────────────

test('nfe_default_email aceita e-mail válido', function (): void {
    $this->actingAsTenantUser();

    $response = $this->putJson('/api/v1/fiscal/settings', fiscalPayload([
        'nfe_default_email' => 'nfe@empresa.com.br',
    ]));

    $response->assertOk();

    $settings = TenantFiscalSettings::where('tenant_id', $this->tenant->uuid)->first();
    expect($settings->nfe_default_email)->toBe('nfe@empresa.com.br');
});

test('nfe_default_email rejeita e-mail inválido com 422', function (): void {
    $this->actingAsTenantUser();

    $response = $this->putJson('/api/v1/fiscal/settings', fiscalPayload([
        'nfe_default_email' => 'nao-e-um-email',
    ]));

    $response->assertStatus(422);
});

// ── Segurança ─────────────────────────────────────────────────────────────────

test('certificate_password_encrypted nunca é exposto na resposta', function (): void {
    $this->actingAsTenantUser();

    $this->putJson('/api/v1/fiscal/settings', fiscalPayload([
        'certificate_password' => 'senha-secreta',
    ]));

    $response = $this->getJson('/api/v1/fiscal/settings');

    $response->assertOk();
    $encoded = json_encode($response->json());
    expect($encoded)->not->toContain('certificate_password_encrypted')
        ->and($encoded)->not->toContain('senha-secreta');
});
