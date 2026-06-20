<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Variant;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Enums\SaleStatusEnum;
use App\Modules\Sales\Models\CashRegisterSession;
use App\Modules\Sales\Models\PaymentTransaction;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    $this->store   = Store::factory()->create();
    $this->variant = Variant::factory()->create();

    InventoryBalance::factory()
        ->for($this->store, 'store')
        ->for($this->variant, 'variant')
        ->create(['quantity' => 100, 'reserved_quantity' => 0]);
});

// ── CREATE ────────────────────────────────────────────────────────────────────

describe('POST /sales', function (): void {
    it('cria uma venda em status DRAFT com itens', function (): void {
        $this->postJson('/api/v1/sales', [
            'store_id' => $this->store->uuid,
            'items'    => [[
                'variant_id'       => $this->variant->uuid,
                'sku_snapshot'     => 'ARG-001',
                'name_snapshot'    => 'Argamassa ACIII 20kg',
                'quantity'         => 2,
                'unit_price_cents' => 5000,
            ]],
        ])->assertCreated()
            ->assertJsonPath('data.status', SaleStatusEnum::Draft->value)
            ->assertJsonPath('data.total_cents', 10000);
    });

    it('é idempotente com sync_uuid — retorna venda existente', function (): void {
        $syncUuid = fake()->uuid();

        $first = $this->postJson('/api/v1/sales', [
            'store_id'  => $this->store->uuid,
            'sync_uuid' => $syncUuid,
            'items'     => [[
                'sku_snapshot'     => 'CIM-001',
                'name_snapshot'    => 'Cimento CP II-E 50kg',
                'quantity'         => 1,
                'unit_price_cents' => 3000,
            ]],
        ])->assertCreated()->json('data.uuid');

        $second = $this->postJson('/api/v1/sales', [
            'store_id'  => $this->store->uuid,
            'sync_uuid' => $syncUuid,
            'items'     => [[
                'sku_snapshot'     => 'CIM-001',
                'name_snapshot'    => 'Cimento CP II-E 50kg',
                'quantity'         => 1,
                'unit_price_cents' => 3000,
            ]],
        ])->assertCreated()->json('data.uuid');

        expect($first)->toBe($second);
        $this->assertDatabaseCount('sales', 1);
    });

    it('calcula subtotal e total corretamente', function (): void {
        $this->postJson('/api/v1/sales', [
            'store_id' => $this->store->uuid,
            'items'    => [
                ['sku_snapshot' => 'A', 'name_snapshot' => 'A', 'quantity' => 2, 'unit_price_cents' => 3000, 'discount_amount_cents' => 500],
                ['sku_snapshot' => 'B', 'name_snapshot' => 'B', 'quantity' => 1, 'unit_price_cents' => 5000],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.subtotal_cents', 10500)  // (2*3000-500) + 5000
            ->assertJsonPath('data.total_cents', 10500);
    });
});

// ── COMPLETE ──────────────────────────────────────────────────────────────────

describe('POST /sales/{sale}/complete', function (): void {
    it('conclui venda e debita estoque', function (): void {
        // Venda de PDV (canal padrão) exige sessão de caixa aberta (A7-02).
        $session = CashRegisterSession::factory()->open()->create(['store_id' => $this->store->uuid]);
        $sale = Sale::factory()->for($this->store, 'store')->withTotal(10000)->draft()->create([
            'session_id' => $session->uuid,
        ]);
        SaleItem::factory()->create([
            'sale_id'            => $sale->uuid,
            'product_variant_id' => $this->variant->uuid,
            'quantity'           => 3,
            'subtotal_cents'     => 10000,
            'total_cents'        => 10000,
        ]);
        PaymentTransaction::factory()->forSale($sale)->paid()->create();

        $this->postJson("/api/v1/sales/{$sale->uuid}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', SaleStatusEnum::Completed->value);

        $this->assertDatabaseHas('inventory_balances', [
            'variant_id' => $this->variant->uuid,
            'quantity'   => 97,
        ]);
    });

    it('rejeita conclusão de venda PDV sem caixa aberto (A7-02)', function (): void {
        // Venda PDV totalmente paga, porém sem sessão de caixa aberta.
        $sale = Sale::factory()->for($this->store, 'store')->withTotal(10000)->draft()->create();
        SaleItem::factory()->create([
            'sale_id'            => $sale->uuid,
            'product_variant_id' => $this->variant->uuid,
            'quantity'           => 1,
            'subtotal_cents'     => 10000,
            'total_cents'        => 10000,
        ]);
        PaymentTransaction::factory()->forSale($sale)->paid()->create();

        $this->postJson("/api/v1/sales/{$sale->uuid}/complete")
            ->assertStatus(422);

        // Estoque não foi debitado (venda não concluída).
        expect($sale->fresh()->status)->toBe(SaleStatusEnum::Draft);
    });

    it('rejeita conclusão sem pagamento suficiente', function (): void {
        $sale = Sale::factory()->for($this->store, 'store')->withTotal(10000)->draft()->create();
        SaleItem::factory()->create([
            'sale_id'        => $sale->uuid,
            'quantity'       => 1,
            'subtotal_cents' => 10000,
            'total_cents'    => 10000,
        ]);

        $this->postJson("/api/v1/sales/{$sale->uuid}/complete")
            ->assertStatus(422);
    });

    it('rejeita conclusão de venda já cancelada', function (): void {
        $sale = Sale::factory()->for($this->store, 'store')->cancelled()->create();

        $this->postJson("/api/v1/sales/{$sale->uuid}/complete")
            ->assertStatus(422);
    });
});

// ── CANCEL ────────────────────────────────────────────────────────────────────

describe('POST /sales/{sale}/cancel', function (): void {
    it('cancela venda em DRAFT sem reverter estoque', function (): void {
        $sale = Sale::factory()->for($this->store, 'store')->draft()->create();

        $this->postJson("/api/v1/sales/{$sale->uuid}/cancel", ['reason' => 'Teste'])
            ->assertOk()
            ->assertJsonPath('data.status', SaleStatusEnum::Cancelled->value);

        $this->assertDatabaseHas('inventory_balances', ['quantity' => 100]);
    });

    it('cancela venda COMPLETED e reverte estoque', function (): void {
        $sale = Sale::factory()->for($this->store, 'store')->withTotal(5000)->completed()->create();
        SaleItem::factory()->create([
            'sale_id'            => $sale->uuid,
            'product_variant_id' => $this->variant->uuid,
            'quantity'           => 5,
            'subtotal_cents'     => 5000,
            'total_cents'        => 5000,
        ]);

        // Simula que o estoque foi decrementado na conclusão
        InventoryBalance::where('variant_id', $this->variant->uuid)->update(['quantity' => 95]);
        PaymentTransaction::factory()->forSale($sale)->paid()->create();

        $this->postJson("/api/v1/sales/{$sale->uuid}/cancel")
            ->assertOk();

        // Estorno deve restaurar 5 unidades
        $this->assertDatabaseHas('inventory_balances', [
            'variant_id' => $this->variant->uuid,
            'quantity'   => 100,
        ]);
    });

    it('não cancela venda já cancelada', function (): void {
        $sale = Sale::factory()->for($this->store, 'store')->cancelled()->create();

        $this->postJson("/api/v1/sales/{$sale->uuid}/cancel")
            ->assertStatus(422);
    });
});
