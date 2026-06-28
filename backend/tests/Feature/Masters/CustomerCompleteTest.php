<?php

declare(strict_types=1);

use App\Core\Features\FeatureEnum;
use App\Core\Tenancy\Services\FeatureManager;
use App\Modules\Customers\Enums\GenderEnum;
use App\Modules\Customers\Enums\MaritalStatusEnum;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\CustomerAsset;
use App\Modules\Customers\Models\CustomerAuthorizedBuyer;
use App\Modules\Customers\Models\CustomerBankReference;
use App\Modules\Customers\Models\CustomerGuarantor;
use App\Modules\Customers\Services\CustomerFinancialSummaryService;

// ─── Helpers de feature flag para testes ────────────────────────────────────
function enableFeature(FeatureEnum $feature): void
{
    $tenantId = \App\Core\Tenancy\Services\TenantContext::getIdOrFail();
    app(FeatureManager::class)->enable($tenantId, $feature);
}

function disableFeature(FeatureEnum $feature): void
{
    $tenantId = \App\Core\Tenancy\Services\TenantContext::getIdOrFail();
    app(FeatureManager::class)->disable($tenantId, $feature);
}

// ─── Bootstrap ──────────────────────────────────────────────────────────────
beforeEach(function (): void {
    $this->actingAsTenantUser();
});

// ════════════════════════════════════════════════════════════════════════════
// BLOCO 1 & 2 — Dados gerais e pessoais
// ════════════════════════════════════════════════════════════════════════════

describe('Dados pessoais PF', function (): void {
    it('cria PF com filiação, profissão e renda e persiste', function (): void {
        $customer = Customer::factory()->create([
            'father_name'    => 'João Silva',
            'mother_name'    => 'Maria Silva',
            'gender'         => GenderEnum::Male->value,
            'marital_status' => MaritalStatusEnum::Married->value,
            'nationality'    => 'Brasileira',
            'birth_city'     => 'Parauapebas',
            'birth_state'    => 'PA',
            'profession'     => 'Engenheiro',
            'employer'       => 'Empresa ABC',
            'monthly_income' => 5000.00,
            'other_income'   => 500.00,
        ]);

        $customer->refresh();
        expect($customer->father_name)->toBe('João Silva')
            ->and($customer->mother_name)->toBe('Maria Silva')
            ->and($customer->gender)->toBe(GenderEnum::Male)
            ->and($customer->marital_status)->toBe(MaritalStatusEnum::Married)
            ->and($customer->totalIncome())->toBe(5500.0);
    });

    it('cria PJ com capital social e retenções fiscais', function (): void {
        $customer = Customer::factory()->create([
            'capital_stock_cents'  => 100_000_00,
            'withholds_pis_cofins' => true,
            'withholds_irpj'       => true,
            'withholds_iss'        => true,
            'iss_rate'             => 2.50,
        ]);

        $customer->refresh();
        expect($customer->capital_stock_cents)->toBe(100_000_00)
            ->and($customer->withholds_pis_cofins)->toBeTrue()
            ->and((float) $customer->iss_rate)->toBe(2.50);
    });

    it('maritalStatus enum retorna label correto em português', function (): void {
        expect(MaritalStatusEnum::Married->label())->toBe('Casado(a)')
            ->and(MaritalStatusEnum::StableUnion->label())->toBe('União estável')
            ->and(MaritalStatusEnum::Divorced->label())->toBe('Divorciado(a)');
    });

    it('maritalStatus hasSpouse retorna true para casado e união estável', function (): void {
        expect(MaritalStatusEnum::Married->hasSpouse())->toBeTrue()
            ->and(MaritalStatusEnum::StableUnion->hasSpouse())->toBeTrue()
            ->and(MaritalStatusEnum::Single->hasSpouse())->toBeFalse();
    });

    it('is_final_consumer padrão é true e isFinalConsumer() funciona', function (): void {
        $c1 = Customer::factory()->create(['is_final_consumer' => true]);
        $c2 = Customer::factory()->create(['is_final_consumer' => false]);

        expect($c1->isFinalConsumer())->toBeTrue()
            ->and($c2->isFinalConsumer())->toBeFalse();
    });

    it('totalIncome soma monthly + other + spouse monthly', function (): void {
        $customer = Customer::factory()->make([
            'monthly_income'        => 3000,
            'other_income'          => 500,
            'spouse_monthly_income' => 2000,
        ]);
        expect($customer->totalIncome())->toBe(5500.0);
    });
});

// ════════════════════════════════════════════════════════════════════════════
// Cônjuge (feature-gated)
// ════════════════════════════════════════════════════════════════════════════

describe('Cônjuge (feature customer.spouse)', function (): void {
    it('feature ativa → spouse aparece no resource', function (): void {
        enableFeature(FeatureEnum::CustomerSpouse);
        $customer = Customer::factory()->create([
            'spouse_name' => 'Ana Silva',
            'spouse_cpf'  => '123.456.789-00',
        ]);

        $this->getJson("/api/v1/customers/{$customer->uuid}")
            ->assertOk()
            ->assertJsonPath('data.spouse.name', 'Ana Silva')
            ->assertJsonPath('data.spouse.cpf', '123.456.789-00');
    });

    it('feature desativada → spouse não aparece no resource', function (): void {
        disableFeature(FeatureEnum::CustomerSpouse);
        $customer = Customer::factory()->create(['spouse_name' => 'Ana Silva']);

        $this->getJson("/api/v1/customers/{$customer->uuid}")
            ->assertOk()
            ->assertJsonMissingPath('data.spouse');
    });
});

// ════════════════════════════════════════════════════════════════════════════
// BLOCO 6 — Dependentes autorizados (crediário)
// ════════════════════════════════════════════════════════════════════════════

describe('Dependentes autorizados (feature sales.installment)', function (): void {
    it('feature desativada → /authorized-buyers retorna 403', function (): void {
        disableFeature(FeatureEnum::SalesInstallment);
        $customer = Customer::factory()->create();

        $this->getJson("/api/v1/customers/{$customer->uuid}/authorized-buyers")
            ->assertForbidden();
    });

    it('feature ativa → CRUD de authorized_buyers funciona', function (): void {
        enableFeature(FeatureEnum::SalesInstallment);
        $customer = Customer::factory()->create();

        $this->postJson("/api/v1/customers/{$customer->uuid}/authorized-buyers", [
            'name'          => 'Filho João',
            'cpf'           => '111.222.333-44',
            'relationship'  => 'Filho',
            'authorized_at' => today()->toDateString(),
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Filho João')
            ->assertJsonPath('data.is_valid', true);

        $this->getJson("/api/v1/customers/{$customer->uuid}/authorized-buyers")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('isValid() retorna false para autorização expirada', function (): void {
        $buyer = CustomerAuthorizedBuyer::factory()->create([
            'is_active'     => true,
            'valid_until'   => today()->subDay()->toDateString(),
            'authorized_at' => today()->subMonth()->toDateString(),
        ]);

        expect($buyer->isValid())->toBeFalse();
    });

    it('isValid() retorna true quando sem data de expiração', function (): void {
        $buyer = CustomerAuthorizedBuyer::factory()->create([
            'is_active'     => true,
            'valid_until'   => null,
            'authorized_at' => today()->toDateString(),
        ]);

        expect($buyer->isValid())->toBeTrue();
    });

    it('revoke() marca como inativo e salva motivo', function (): void {
        $customer = Customer::factory()->create();
        $buyer    = CustomerAuthorizedBuyer::factory()->create([
            'customer_id'   => $customer->uuid,
            'is_active'     => true,
            'authorized_at' => today()->toDateString(),
        ]);

        $buyer->revoke('Perda de documento');
        $buyer->refresh();

        expect($buyer->is_active)->toBeFalse()
            ->and($buyer->revoked_reason)->toBe('Perda de documento')
            ->and($buyer->revoked_at)->not->toBeNull();
    });
});

// ════════════════════════════════════════════════════════════════════════════
// BLOCO 3 — Bens materiais
// ════════════════════════════════════════════════════════════════════════════

describe('Bens materiais (feature customer.credit_analysis)', function (): void {
    it('cria 2 imóveis e 1 veículo → totalAssetsValue retorna soma', function (): void {
        enableFeature(FeatureEnum::CustomerCreditAnalysis);
        $customer = Customer::factory()->create();

        foreach ([
            ['real_estate', 'Casa na Rua X', 200_000_00],
            ['real_estate', 'Terreno',        80_000_00],
            ['vehicle',     'Gol 2018',       35_000_00],
        ] as [$type, $desc, $value]) {
            CustomerAsset::create([
                'tenant_id'             => $customer->tenant_id,
                'customer_id'           => $customer->uuid,
                'asset_type'            => $type,
                'description'           => $desc,
                'estimated_value_cents' => $value,
            ]);
        }

        expect(CustomerAsset::totalAssetsValue($customer->uuid))->toBe(315_000_00);
    });

    it('soft delete de bem → não aparece na listagem API', function (): void {
        enableFeature(FeatureEnum::CustomerCreditAnalysis);
        $customer = Customer::factory()->create();
        $asset    = CustomerAsset::create([
            'tenant_id'   => $customer->tenant_id,
            'customer_id' => $customer->uuid,
            'asset_type'  => 'vehicle',
            'description' => 'Carro',
        ]);

        $this->deleteJson("/api/v1/customers/{$customer->uuid}/assets/{$asset->uuid}")
            ->assertNoContent();

        $this->getJson("/api/v1/customers/{$customer->uuid}/assets")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('POST asset cria bem material via API', function (): void {
        enableFeature(FeatureEnum::CustomerCreditAnalysis);
        $customer = Customer::factory()->create();

        $this->postJson("/api/v1/customers/{$customer->uuid}/assets", [
            'asset_type'            => 'real_estate',
            'description'           => 'Apartamento Centro',
            'estimated_value_cents' => 150_000_00,
        ])->assertCreated()
            ->assertJsonPath('data.asset_type', 'real_estate')
            ->assertJsonPath('data.estimated_value_cents', 150_000_00);
    });
});

// ════════════════════════════════════════════════════════════════════════════
// BLOCO 4 — Referências bancárias
// ════════════════════════════════════════════════════════════════════════════

describe('Referências bancárias', function (): void {
    it('cria 3 referências bancárias e lista', function (): void {
        $customer = Customer::factory()->create();

        foreach (['Banco A', 'Banco B', 'Banco C'] as $bank) {
            CustomerBankReference::create([
                'tenant_id'   => $customer->tenant_id,
                'customer_id' => $customer->uuid,
                'bank_name'   => $bank,
            ]);
        }

        $this->getJson("/api/v1/customers/{$customer->uuid}/bank-references")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('cria referência bancária via API com campos completos', function (): void {
        $customer = Customer::factory()->create();

        $this->postJson("/api/v1/customers/{$customer->uuid}/bank-references", [
            'bank_name'                    => 'Caixa Econômica',
            'bank_agency'                  => '0001',
            'account_type'                 => 'checking',
            'first_purchase_value_cents'   => 1_500_00,
            'highest_purchase_value_cents' => 5_000_00,
        ])->assertCreated()
            ->assertJsonPath('data.bank_name', 'Caixa Econômica')
            ->assertJsonPath('data.account_type', 'checking');
    });

    it('soft delete de referência bancária funciona', function (): void {
        $customer = Customer::factory()->create();
        $ref      = CustomerBankReference::create([
            'tenant_id'   => $customer->tenant_id,
            'customer_id' => $customer->uuid,
            'bank_name'   => 'Banco X',
        ]);

        $this->deleteJson("/api/v1/customers/{$customer->uuid}/bank-references/{$ref->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('customer_bank_references', ['uuid' => $ref->uuid]);
    });
});

// ════════════════════════════════════════════════════════════════════════════
// BLOCO 7 — Resumo financeiro
// ════════════════════════════════════════════════════════════════════════════

describe('Resumo financeiro', function (): void {
    it('CustomerFinancialSummaryService retorna DTO com zeros quando sem dados', function (): void {
        $customer = Customer::factory()->create(['credit_limit_cents' => 10_000_00]);
        $service  = app(CustomerFinancialSummaryService::class);

        $summary = $service->getSummary($customer);

        expect($summary->open_invoices_count)->toBe(0)
            ->and($summary->credit_limit_cents)->toBe(10_000_00)
            ->and($summary->credit_available_cents)->toBe(10_000_00);
    });

    it('GET financial-summary retorna 200 com estrutura correta', function (): void {
        $customer = Customer::factory()->create();

        $this->getJson("/api/v1/customers/{$customer->uuid}/financial-summary")
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'open_invoices_count',
                'credit_limit_cents',
                'credit_available_cents',
                'days_without_purchase',
            ]]);
    });

    it('invalidate limpa cache do cliente', function (): void {
        $customer = Customer::factory()->create();
        $service  = app(CustomerFinancialSummaryService::class);

        $service->getSummary($customer);
        $service->invalidate($customer->tenant_id, $customer->uuid);

        $summary = $service->getSummary($customer);
        expect($summary)->not->toBeNull();
    });
});

// ════════════════════════════════════════════════════════════════════════════
// BLOCO 10 — Avalista completo
// ════════════════════════════════════════════════════════════════════════════

describe('Avalista completo (BLOCO 10)', function (): void {
    it('cria avalista com campos complementares via API', function (): void {
        enableFeature(FeatureEnum::CustomerGuarantor);
        $customer = Customer::factory()->create();

        $this->postJson("/api/v1/customers/{$customer->uuid}/guarantors", [
            'guarantor_type'              => 'person',
            'name'                        => 'Roberto Avalista',
            'document'                    => '999.888.777-66',
            'phone'                       => '(94) 99999-0000',
            'monthly_income'              => 4000.00,
            'other_income'                => 500.00,
            'marital_status'              => MaritalStatusEnum::Married->value,
            'housing_type'                => 'own',
            'years_at_address'            => 10,
            'is_same_address_as_customer' => false,
            'assets_description'          => 'Casa própria avaliada em R$ 200.000',
        ])->assertCreated();

        $guarantor = CustomerGuarantor::where('customer_id', $customer->uuid)->first();

        expect($guarantor->marital_status)->toBe(MaritalStatusEnum::Married)
            ->and($guarantor->years_at_address)->toBe(10)
            ->and($guarantor->totalIncome())->toBe(4500.0);
    });
});

// ════════════════════════════════════════════════════════════════════════════
// Análise de crédito
// ════════════════════════════════════════════════════════════════════════════

describe('Análise de crédito', function (): void {
    it('registra análise de crédito e retorna score', function (): void {
        enableFeature(FeatureEnum::CustomerCreditAnalysis);
        $customer = Customer::factory()->create();

        $this->postJson("/api/v1/customers/{$customer->uuid}/credit-analysis", [
            'credit_score' => 8,
            'credit_notes' => 'Cliente com histórico limpo',
            'spc_status'   => 'clean',
        ])->assertOk()
            ->assertJsonPath('data.credit.credit_score', 8)
            ->assertJsonPath('data.credit.credit_score_label', 'Bom');
    });

    it('bloqueia análise sem a feature habilitada', function (): void {
        disableFeature(FeatureEnum::CustomerCreditAnalysis);
        $customer = Customer::factory()->create();

        $this->postJson("/api/v1/customers/{$customer->uuid}/credit-analysis", [
            'credit_score' => 7,
        ])->assertForbidden();
    });

    it('creditScoreLabel retorna label correto para cada faixa', function (): void {
        $customer = Customer::factory()->make();

        $customer->credit_score = 10;
        expect($customer->creditScoreLabel())->toBe('Excelente');

        $customer->credit_score = 7;
        expect($customer->creditScoreLabel())->toBe('Bom');

        $customer->credit_score = 5;
        expect($customer->creditScoreLabel())->toBe('Regular');

        $customer->credit_score = null;
        expect($customer->creditScoreLabel())->toBe('Não analisado');
    });
});

// ════════════════════════════════════════════════════════════════════════════
// Avalistas normalizados
// ════════════════════════════════════════════════════════════════════════════

describe('Avalistas normalizados', function (): void {
    it('cria avalista com dados completos', function (): void {
        enableFeature(FeatureEnum::CustomerGuarantor);
        $customer = Customer::factory()->create();

        $this->postJson("/api/v1/customers/{$customer->uuid}/guarantors", [
            'guarantor_type' => 'person',
            'name'           => 'Fiador Silva',
            'document'       => '111.222.333-44',
            'phone'          => '(11) 99999-1111',
            'monthly_income' => 5000.00,
            'relationship'   => 'Cônjuge',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Fiador Silva');
    });

    it('lista avalistas do cliente', function (): void {
        enableFeature(FeatureEnum::CustomerGuarantor);
        $customer = Customer::factory()->create();

        CustomerGuarantor::create([
            'tenant_id'   => $customer->tenant_id,
            'customer_id' => $customer->uuid,
            'name'        => 'Avalista Um',
            'document'    => '111.111.111-11',
        ]);

        $this->getJson("/api/v1/customers/{$customer->uuid}/guarantors")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('bloqueia acesso sem a feature habilitada', function (): void {
        disableFeature(FeatureEnum::CustomerGuarantor);
        $customer = Customer::factory()->create();

        $this->getJson("/api/v1/customers/{$customer->uuid}/guarantors")
            ->assertForbidden();
    });

    it('exclui avalista', function (): void {
        enableFeature(FeatureEnum::CustomerGuarantor);
        $customer  = Customer::factory()->create();
        $guarantor = CustomerGuarantor::create([
            'tenant_id'   => $customer->tenant_id,
            'customer_id' => $customer->uuid,
            'name'        => 'Avalista Temp',
            'document'    => '999.999.999-99',
        ]);

        $this->deleteJson("/api/v1/customers/{$customer->uuid}/guarantors/{$guarantor->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('customer_guarantors', ['uuid' => $guarantor->uuid]);
    });
});

// ════════════════════════════════════════════════════════════════════════════
// Interações / CRM
// ════════════════════════════════════════════════════════════════════════════

describe('Interações / CRM', function (): void {
    it('registra interação de visita', function (): void {
        $customer = Customer::factory()->create();

        $this->postJson("/api/v1/customers/{$customer->uuid}/interactions", [
            'interaction_type' => 'visit',
            'description'      => 'Visita comercial realizada',
            'interacted_at'    => now()->toDateTimeString(),
        ])->assertCreated()
            ->assertJsonPath('data.interaction_type', 'visit');
    });

    it('lista interações com paginação', function (): void {
        $customer = Customer::factory()->create();

        \App\Modules\Customers\Models\CustomerInteraction::create([
            'tenant_id'        => $customer->tenant_id,
            'customer_id'      => $customer->uuid,
            'interaction_type' => 'call',
            'description'      => 'Ligação de follow-up',
            'created_by'       => $this->user->uuid,
            'interacted_at'    => now(),
        ]);

        $this->getJson("/api/v1/customers/{$customer->uuid}/interactions")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

// ════════════════════════════════════════════════════════════════════════════
// CustomerResource — feature gates
// ════════════════════════════════════════════════════════════════════════════

describe('CustomerResource — feature gates', function (): void {
    it('expõe bloco cônjuge quando feature customer.spouse ativa', function (): void {
        enableFeature(FeatureEnum::CustomerSpouse);
        $customer = Customer::factory()->create(['spouse_name' => 'Cônjuge Teste']);

        $this->getJson("/api/v1/customers/{$customer->uuid}")
            ->assertOk()
            ->assertJsonPath('data.spouse.name', 'Cônjuge Teste');
    });

    it('não expõe bloco cônjuge quando feature customer.spouse desativada', function (): void {
        disableFeature(FeatureEnum::CustomerSpouse);
        $customer = Customer::factory()->create(['spouse_name' => 'Cônjuge Teste']);

        $this->getJson("/api/v1/customers/{$customer->uuid}")
            ->assertOk()
            ->assertJsonMissingPath('data.spouse');
    });

    it('expõe bloco crédito quando feature customer.credit_analysis ativa', function (): void {
        enableFeature(FeatureEnum::CustomerCreditAnalysis);
        $customer = Customer::factory()->create(['credit_score' => 8]);

        $this->getJson("/api/v1/customers/{$customer->uuid}")
            ->assertOk()
            ->assertJsonPath('data.credit.credit_score', 8);
    });
});

// ════════════════════════════════════════════════════════════════════════════
// Spouse helpers
// ════════════════════════════════════════════════════════════════════════════

describe('Spouse helpers', function (): void {
    it('hasSpouseData retorna true quando há dados do cônjuge', function (): void {
        $customer = Customer::factory()->make(['spouse_name' => 'Ana']);
        expect($customer->hasSpouseData())->toBeTrue();
    });

    it('hasSpouseData retorna false quando sem dados de cônjuge', function (): void {
        $customer = Customer::factory()->make([
            'spouse_name' => null, 'spouse_document' => null, 'spouse_cpf' => null,
        ]);
        expect($customer->hasSpouseData())->toBeFalse();
    });

    it('hasIncome retorna true quando monthly_income positivo', function (): void {
        $customer = Customer::factory()->make(['monthly_income' => 3000]);
        expect($customer->hasIncome())->toBeTrue();
    });
});
