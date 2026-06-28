<?php

declare(strict_types=1);

use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Purchasing\Models\SupplierEvaluation;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

// ────────────────────────────────────────────────────────────────────────────
describe('Avaliações de fornecedor', function (): void {
    it('cria avaliação e calcula overall_score automaticamente', function (): void {
        $supplier = Supplier::factory()->create();

        $this->postJson("/api/v1/purchasing/suppliers/{$supplier->uuid}/evaluations", [
            'reference_date'  => '2026-06-01',
            'delivery_score'  => 5,
            'quality_score'   => 4,
            'price_score'     => 3,
            'service_score'   => 4,
            'avg_delivery_days' => 5,
        ])
            ->assertCreated()
            ->assertJsonPath('data.overall_score', '4.00');

        $this->assertDatabaseHas('supplier_evaluations', [
            'supplier_id'   => $supplier->uuid,
            'overall_score' => 4.00,
        ]);
    });

    it('lista avaliações do fornecedor', function (): void {
        $supplier = Supplier::factory()->create();

        SupplierEvaluation::create([
            'tenant_id'      => $this->tenant->uuid,
            'supplier_id'    => $supplier->uuid,
            'reference_date' => '2026-05-01',
            'delivery_score' => 5,
            'quality_score'  => 5,
            'price_score'    => 5,
            'service_score'  => 5,
            'overall_score'  => 5.0,
            'evaluated_by'   => $this->user->uuid,
        ]);

        $this->getJson("/api/v1/purchasing/suppliers/{$supplier->uuid}/evaluations")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('atualiza métricas denormalizadas do fornecedor após avaliação', function (): void {
        $supplier = Supplier::factory()->create();

        $this->postJson("/api/v1/purchasing/suppliers/{$supplier->uuid}/evaluations", [
            'reference_date'        => '2026-06-01',
            'delivery_score'        => 4,
            'quality_score'         => 4,
            'price_score'           => 4,
            'service_score'         => 4,
            'avg_delivery_days'     => 7,
            'on_time_delivery_rate' => 90.5,
        ])->assertCreated();

        $supplier->refresh();
        expect((float) $supplier->performance_score)->toBe(4.0)
            ->and((int) $supplier->avg_delivery_days)->toBe(7);
    });

    it('averageScore retorna média geral das avaliações', function (): void {
        $supplier = Supplier::factory()->create();

        SupplierEvaluation::create([
            'tenant_id'      => $this->tenant->uuid,
            'supplier_id'    => $supplier->uuid,
            'reference_date' => '2026-04-01',
            'delivery_score' => 4,
            'quality_score'  => 4,
            'price_score'    => 4,
            'service_score'  => 4,
            'overall_score'  => 4.0,
            'evaluated_by'   => $this->user->uuid,
        ]);

        expect($supplier->refresh()->averageScore())->toBe(4.0);
    });
});

// ────────────────────────────────────────────────────────────────────────────
describe('Banking estendido de fornecedor', function (): void {
    it('armazena bank_code e bank_pix_type', function (): void {
        $supplier = Supplier::factory()->create([
            'bank_code'            => '341',
            'bank_account_holder'  => 'Empresa Ltda',
            'bank_pix_type'        => 'cnpj',
        ]);

        expect($supplier->bank_code)->toBe('341')
            ->and($supplier->bank_pix_type)->toBe('cnpj');
    });
});
