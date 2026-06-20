<?php

declare(strict_types=1);

use App\Modules\Fiscal\Models\TaxProfile;
use App\Modules\Fiscal\Models\TenantFiscalSettings;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('PUT /fiscal/settings — configuração fiscal', function (): void {
    it('manager configura fiscal settings pela primeira vez', function (): void {
        $this->putJson('/api/v1/fiscal/settings', [
            'company_name' => 'Construtora Demo S/A',
            'cnpj'         => '12.345.678/0001-90',
            'ie'           => '123.456.789.000',
            'crt'          => 1,
            'is_active'    => true,
        ])->assertOk()
            ->assertJsonPath('data.company_name', 'Construtora Demo S/A')
            ->assertJsonPath('data.crt', 1)
            ->assertJsonPath('data.has_csc', false);

        $this->assertDatabaseHas('tenant_fiscal_settings', [
            'tenant_id'    => $this->tenant->uuid,
            'company_name' => 'Construtora Demo S/A',
        ]);
    });

    it('atualiza settings existente (upsert)', function (): void {
        TenantFiscalSettings::factory()->create([
            'tenant_id'    => $this->tenant->uuid,
            'company_name' => 'Antiga Razão Social',
        ]);

        $this->putJson('/api/v1/fiscal/settings', [
            'company_name' => 'Nova Razão Social',
            'cnpj'         => '12.345.678/0001-90',
            'crt'          => 1,
        ])->assertOk()
            ->assertJsonPath('data.company_name', 'Nova Razão Social');

        expect(TenantFiscalSettings::where('tenant_id', $this->tenant->uuid)->count())->toBe(1);
    });

    it('salva CSC quando fornecido', function (): void {
        $this->putJson('/api/v1/fiscal/settings', [
            'company_name' => 'Loja Test',
            'cnpj'         => '12.345.678/0001-90',
            'crt'          => 1,
            'csc'          => '550e8400-e29b-41d4-a716-446655440000',
            'csc_id'       => '000001',
        ])->assertOk()
            ->assertJsonPath('data.has_csc', true)
            ->assertJsonPath('data.csc_id', '000001');
    });

    it('operador não pode configurar fiscal', function (): void {
        $this->restrictUserToPermissions(); // sem permissão de settings.update

        $this->putJson('/api/v1/fiscal/settings', [
            'company_name' => 'Loja',
            'cnpj'         => '12.345.678/0001-90',
            'crt'          => 1,
        ])->assertStatus(403);
    });

    it('não expõe certificate_password_encrypted na resposta', function (): void {
        $this->putJson('/api/v1/fiscal/settings', [
            'company_name'          => 'Loja Segura',
            'cnpj'                  => '12.345.678/0001-90',
            'crt'                   => 1,
            'certificate_password'  => 'senha_super_secreta',
        ])->assertOk()
            ->assertJsonMissingPath('data.certificate_password_encrypted')
            ->assertJsonMissingPath('data.certificate_password');
    });

    it('rejeita CRT inválido', function (): void {
        $this->putJson('/api/v1/fiscal/settings', [
            'company_name' => 'Loja',
            'cnpj'         => '12.345.678/0001-90',
            'crt'          => 9, // só aceita 1, 2 ou 3
        ])->assertStatus(422);
    });
});

describe('GET /fiscal/settings — consulta configuração', function (): void {
    it('retorna null quando não configurado', function (): void {
        $this->getJson('/api/v1/fiscal/settings')
            ->assertOk()
            ->assertJsonPath('data', null);
    });

    it('retorna settings existente', function (): void {
        TenantFiscalSettings::factory()->create([
            'tenant_id'    => $this->tenant->uuid,
            'company_name' => 'Minha Loja',
        ]);

        $this->getJson('/api/v1/fiscal/settings')
            ->assertOk()
            ->assertJsonPath('data.company_name', 'Minha Loja');
    });
});

describe('Tax Profiles — perfis tributários', function (): void {
    it('cria perfil fiscal', function (): void {
        $this->postJson('/api/v1/fiscal/tax-profiles', [
            'name'   => 'Materiais Construção Simples',
            'regime' => 'simples_nacional',
        ])->assertStatus(201)
            ->assertJsonPath('data.name', 'Materiais Construção Simples')
            ->assertJsonPath('data.regime', 'simples_nacional');
    });

    it('lista perfis do tenant', function (): void {
        TaxProfile::factory()->count(3)->create();

        $this->getJson('/api/v1/fiscal/tax-profiles')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('filtra por regime', function (): void {
        TaxProfile::factory()->simplesNacional()->create();
        TaxProfile::factory()->lucroPresumido()->create();

        $this->getJson('/api/v1/fiscal/tax-profiles?regime=simples_nacional')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('atualiza perfil', function (): void {
        $profile = TaxProfile::factory()->create(['name' => 'Antigo']);

        $this->putJson("/api/v1/fiscal/tax-profiles/{$profile->uuid}", [
            'name'   => 'Novo Nome',
            'regime' => 'lucro_presumido',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Novo Nome')
            ->assertJsonPath('data.regime', 'lucro_presumido');
    });

    it('exclui perfil', function (): void {
        $profile = TaxProfile::factory()->create();

        $this->deleteJson("/api/v1/fiscal/tax-profiles/{$profile->uuid}")
            ->assertStatus(204);

        $this->assertSoftDeleted('tax_profiles', ['uuid' => $profile->uuid]);
    });

    it('não retorna perfis de outro tenant', function (): void {
        TaxProfile::factory()->count(2)->create();

        $this->actingAsTenantUser();
        TaxProfile::factory()->create();

        $this->getJson('/api/v1/fiscal/tax-profiles')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});
