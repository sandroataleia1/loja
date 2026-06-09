<?php

declare(strict_types=1);

use App\Core\Auth\Enums\PermissionEnum;
use App\Modules\Fiscal\Actions\GenerateFiscalDocumentAction;
use App\Modules\Fiscal\Enums\FiscalModeEnum;
use App\Modules\Fiscal\Events\FiscalEmissionQueued;
use App\Modules\Fiscal\Events\FiscalEmissionSkipped;
use App\Modules\Fiscal\Jobs\FiscalIssueJob;
use App\Modules\Fiscal\Jobs\ProcessFiscalDocumentJob;
use App\Modules\Fiscal\Models\FiscalDocument;
use App\Modules\Fiscal\Models\TenantFiscalSettings;
use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\DTOs\CreateSaleDTO;
use App\Modules\Sales\Enums\SaleStatusEnum;
use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    $this->store = Store::factory()->create();
});

// ─────────────────────────────────────────────────────────────────────────────
// fiscal_mode congelado na venda
// ─────────────────────────────────────────────────────────────────────────────

describe('fiscal_mode na venda', function (): void {
    it('venda criada com fiscal_mode explícito preserva o modo', function (): void {
        TenantFiscalSettings::factory()->create([
            'tenant_id'           => $this->tenant->uuid,
            'default_fiscal_mode' => FiscalModeEnum::Nfe->value,
        ]);

        $this->postJson('/api/v1/sales', [
            'store_id'    => $this->store->uuid,
            'fiscal_mode' => 'nfce',
            'items'       => [[
                'sku_snapshot'      => 'SKU-1',
                'name_snapshot'     => 'Produto A',
                'quantity'          => 1,
                'unit_price_cents'  => 10000,
            ]],
        ])->assertStatus(201)
            ->assertJsonPath('data.fiscal_mode', 'nfce');
    });

    it('usa default_fiscal_mode do tenant quando não especificado', function (): void {
        TenantFiscalSettings::factory()->create([
            'tenant_id'           => $this->tenant->uuid,
            'default_fiscal_mode' => FiscalModeEnum::Nfce->value,
        ]);

        $this->postJson('/api/v1/sales', [
            'store_id' => $this->store->uuid,
            'items'    => [[
                'sku_snapshot'     => 'SKU-1',
                'name_snapshot'    => 'Produto B',
                'quantity'         => 1,
                'unit_price_cents' => 5000,
            ]],
        ])->assertStatus(201)
            ->assertJsonPath('data.fiscal_mode', 'nfce');
    });

    it('usa none quando sem configuração fiscal', function (): void {
        $this->postJson('/api/v1/sales', [
            'store_id' => $this->store->uuid,
            'items'    => [[
                'sku_snapshot'     => 'SKU-1',
                'name_snapshot'    => 'Produto C',
                'quantity'         => 1,
                'unit_price_cents' => 5000,
            ]],
        ])->assertStatus(201)
            ->assertJsonPath('data.fiscal_mode', 'none');
    });

    it('fiscal_mode não muda quando a configuração do tenant é alterada depois', function (): void {
        $settings = TenantFiscalSettings::factory()->create([
            'tenant_id'           => $this->tenant->uuid,
            'default_fiscal_mode' => FiscalModeEnum::Nfce->value,
        ]);

        $sale = Sale::factory()->create([
            'store_id'    => $this->store->uuid,
            'fiscal_mode' => FiscalModeEnum::Nfce,
        ]);

        // Tenant muda o default_fiscal_mode para none depois
        $settings->update(['default_fiscal_mode' => FiscalModeEnum::None->value]);

        // Venda continua com o modo original
        expect($sale->fresh()->fiscal_mode)->toBe(FiscalModeEnum::Nfce);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// GenerateFiscalDocumentAction — decisão de emissão
// ─────────────────────────────────────────────────────────────────────────────

describe('GenerateFiscalDocumentAction', function (): void {
    it('emite skip quando fiscal_mode=none', function (): void {
        Event::fake([FiscalEmissionSkipped::class]);

        $sale = Sale::factory()->create([
            'store_id'    => $this->store->uuid,
            'fiscal_mode' => FiscalModeEnum::None,
        ]);

        $action = app(GenerateFiscalDocumentAction::class);
        $result = $action->execute($sale, isAutoTriggered: true);

        expect($result)->toBeNull();
        Event::assertDispatched(FiscalEmissionSkipped::class, fn ($e) => $e->reason === 'fiscal_mode=none');
    });

    it('emite skip quando fiscal_settings inativo', function (): void {
        Event::fake([FiscalEmissionSkipped::class]);

        TenantFiscalSettings::factory()->inactive()->create([
            'tenant_id' => $this->tenant->uuid,
        ]);

        $sale = Sale::factory()->create([
            'store_id'    => $this->store->uuid,
            'fiscal_mode' => FiscalModeEnum::Nfce,
        ]);

        $action = app(GenerateFiscalDocumentAction::class);
        $result = $action->execute($sale, isAutoTriggered: true);

        expect($result)->toBeNull();
        Event::assertDispatched(FiscalEmissionSkipped::class, fn ($e) => $e->reason === 'fiscal_settings_inactive');
    });

    it('emite skip quando auto_issue_nfce=false e isAutoTriggered=true', function (): void {
        Event::fake([FiscalEmissionSkipped::class]);

        TenantFiscalSettings::factory()->noAutoIssue()->create([
            'tenant_id' => $this->tenant->uuid,
        ]);

        $sale = Sale::factory()->create([
            'store_id'    => $this->store->uuid,
            'fiscal_mode' => FiscalModeEnum::Nfce,
        ]);

        $action = app(GenerateFiscalDocumentAction::class);
        $result = $action->execute($sale, isAutoTriggered: true);

        expect($result)->toBeNull();
        Event::assertDispatched(FiscalEmissionSkipped::class, fn ($e) => $e->reason === 'auto_issue_disabled');
    });

    it('emite quando auto_issue_nfce=false mas isAutoTriggered=false (manual)', function (): void {
        Queue::fake();

        TenantFiscalSettings::factory()->noAutoIssue()->create([
            'tenant_id' => $this->tenant->uuid,
        ]);

        $sale = Sale::factory()->create([
            'store_id'    => $this->store->uuid,
            'fiscal_mode' => FiscalModeEnum::Nfce,
        ]);

        $action = app(GenerateFiscalDocumentAction::class);
        $result = $action->execute($sale, isAutoTriggered: false);

        expect($result)->not->toBeNull();
        Queue::assertPushed(FiscalIssueJob::class);
    });

    it('cria FiscalDocument e dispara FiscalEmissionQueued na emissão bem-sucedida', function (): void {
        Event::fake([FiscalEmissionQueued::class]);
        Queue::fake();

        TenantFiscalSettings::factory()->create([
            'tenant_id' => $this->tenant->uuid,
        ]);

        $sale = Sale::factory()->create([
            'store_id'    => $this->store->uuid,
            'fiscal_mode' => FiscalModeEnum::Nfce,
        ]);

        $action = app(GenerateFiscalDocumentAction::class);
        $doc = $action->execute($sale, isAutoTriggered: true);

        expect($doc)->not->toBeNull();
        expect($doc->sale_id)->toBe($sale->uuid);

        Event::assertDispatched(FiscalEmissionQueued::class);
        Queue::assertPushed(FiscalIssueJob::class);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Permissões granulares — emissão manual via API
// ─────────────────────────────────────────────────────────────────────────────

describe('permissões de emissão manual — POST /fiscal/documents', function (): void {
    it('operador pode emitir quando allow_manual_nfce=true', function (): void {
        Queue::fake();

        TenantFiscalSettings::factory()->create([
            'tenant_id'         => $this->tenant->uuid,
            'allow_manual_nfce' => true,
        ]);

        $this->restrictUserToPermissions(); // sem fiscal.issue — accesso via allow_manual_nfce

        $this->postJson('/api/v1/fiscal/documents', [
            'store_id' => $this->store->uuid,
            'type'     => 'nfce',
        ])->assertStatus(201);
    });

    it('operador não pode emitir quando allow_manual_nfce=false', function (): void {
        TenantFiscalSettings::factory()->noManualNfce()->create([
            'tenant_id' => $this->tenant->uuid,
        ]);

        $this->restrictUserToPermissions(); // sem fiscal.issue e allow_manual_nfce=false

        $this->postJson('/api/v1/fiscal/documents', [
            'store_id' => $this->store->uuid,
            'type'     => 'nfce',
        ])->assertStatus(403);
    });

    it('operador não pode emitir sem configuração fiscal cadastrada', function (): void {
        $this->restrictUserToPermissions(); // sem fiscal.issue e sem fiscal settings

        $this->postJson('/api/v1/fiscal/documents', [
            'store_id' => $this->store->uuid,
            'type'     => 'nfce',
        ])->assertStatus(403);
    });

    it('manager sempre pode emitir independente de allow_manual_nfce', function (): void {
        Queue::fake();

        TenantFiscalSettings::factory()->noManualNfce()->create([
            'tenant_id' => $this->tenant->uuid,
        ]);

        $this->postJson('/api/v1/fiscal/documents', [
            'store_id' => $this->store->uuid,
            'type'     => 'nfce',
        ])->assertStatus(201);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Política fiscal exposta no settings
// ─────────────────────────────────────────────────────────────────────────────

describe('PUT /fiscal/settings — campos de política', function (): void {
    it('salva auto_issue_nfce e allow_manual_nfce', function (): void {
        $this->putJson('/api/v1/fiscal/settings', [
            'company_name'       => 'Loja',
            'cnpj'               => '12.345.678/0001-90',
            'crt'                => 1,
            'auto_issue_nfce'    => false,
            'allow_manual_nfce'  => true,
            'default_fiscal_mode' => 'nfce',
        ])->assertOk()
            ->assertJsonPath('data.auto_issue_nfce', false)
            ->assertJsonPath('data.allow_manual_nfce', true)
            ->assertJsonPath('data.default_fiscal_mode', 'nfce');
    });

    it('aceita default_fiscal_mode=none', function (): void {
        $this->putJson('/api/v1/fiscal/settings', [
            'company_name'        => 'Loja',
            'cnpj'                => '12.345.678/0001-90',
            'crt'                 => 1,
            'default_fiscal_mode' => 'none',
        ])->assertOk()
            ->assertJsonPath('data.default_fiscal_mode', 'none');
    });

    it('rejeita default_fiscal_mode inválido', function (): void {
        $this->putJson('/api/v1/fiscal/settings', [
            'company_name'        => 'Loja',
            'cnpj'                => '12.345.678/0001-90',
            'crt'                 => 1,
            'default_fiscal_mode' => 'cupom_fiscal',
        ])->assertStatus(422);
    });
});
