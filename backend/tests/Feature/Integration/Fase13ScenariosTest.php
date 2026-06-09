<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Variant;
use App\Modules\Conditional\Enums\ConditionalStatusEnum;
use App\Modules\Customers\Models\Customer;
use App\Modules\Finance\Enums\FinancialEntryStatusEnum;
use App\Modules\Finance\Enums\FinancialEntryTypeEnum;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Enums\SaleStatusEnum;
use App\Modules\Sales\Models\CashRegisterSession;

/**
 * Etapa 10/11 — Testes de integração ponta a ponta dos fluxos críticos da
 * Fase 13. Cada cenário valida o efeito cruzado entre estoque, financeiro,
 * movimentos e status (não apenas um módulo isolado).
 */
beforeEach(function (): void {
    $this->actingAsTenantUser();
    $this->store   = Store::factory()->create();
    $this->variant = Variant::factory()->create();
});

/** Saldo atual de uma variante numa loja (0 se não existir). */
function stockOf(string $storeUuid, string $variantUuid): int
{
    return (int) (InventoryBalance::where('store_id', $storeUuid)
        ->where('variant_id', $variantUuid)
        ->value('quantity') ?? 0);
}

// ── CENÁRIO 1 — Compra → Recebimento → Entrada Estoque → Contas a Pagar ────────
it('cenário 1: compra → recebimento → entrada de estoque → contas a pagar', function (): void {
    $supplier = \App\Modules\Purchasing\Models\Supplier::factory()->create(['tenant_id' => $this->tenant->uuid]);

    $order = $this->postJson('/api/v1/purchasing/orders', [
        'supplier_id' => $supplier->uuid,
        'store_id'    => $this->store->uuid,
        'order_date'  => today()->toDateString(),
        'items'       => [['product_variant_id' => $this->variant->uuid, 'quantity' => 20, 'unit_cost' => 10.00]],
    ])->assertCreated()->json('data');

    $this->postJson("/api/v1/purchasing/orders/{$order['uuid']}/send")->assertOk();

    $receipt = $this->postJson("/api/v1/purchasing/orders/{$order['uuid']}/receive", [
        'items' => [['order_item_uuid' => $order['items'][0]['uuid'], 'quantity_received' => 20]],
    ])->assertCreated()->json('data');

    // Estoque entrou
    expect(stockOf($this->store->uuid, $this->variant->uuid))->toBe(20);

    // Movimento de entrada
    $this->assertDatabaseHas('inventory_movements', [
        'store_id'       => $this->store->uuid,
        'variant_id'     => $this->variant->uuid,
        'type'           => 'in',
        'reference_type' => 'purchase_receipt',
    ]);

    // Contas a pagar gerada (20 × R$10 = 20000 centavos)
    $payable = FinancialEntry::where('reference_type', 'purchase_receipt')
        ->where('reference_id', $receipt['uuid'])
        ->first();
    expect($payable)->not->toBeNull()
        ->and($payable->type)->toBe(FinancialEntryTypeEnum::Expense)
        ->and($payable->status)->toBe(FinancialEntryStatusEnum::Pending)
        ->and($payable->amount_cents)->toBe(20000);
});

// ── CENÁRIO 2 — Venda → Baixa Estoque → Contas a Receber ──────────────────────
it('cenário 2: venda → baixa de estoque → contas a receber', function (): void {
    InventoryBalance::factory()->for($this->store, 'store')->for($this->variant, 'variant')
        ->create(['quantity' => 10]);
    $session = CashRegisterSession::factory()->open()->create(['store_id' => $this->store->uuid]);

    $sale = $this->postJson('/api/v1/sales', [
        'store_id'   => $this->store->uuid,
        'session_id' => $session->uuid,
        'items'      => [[
            'variant_id'       => $this->variant->uuid,
            'sku_snapshot'     => 'SKU-1',
            'name_snapshot'    => 'Produto',
            'quantity'         => 2,
            'unit_price_cents' => 5000,
        ]],
    ])->assertCreated()->json('data');

    $this->postJson("/api/v1/sales/{$sale['uuid']}/payments", [
        'method' => 'cash', 'amount_cents' => 10000,
    ])->assertCreated();

    $this->postJson("/api/v1/sales/{$sale['uuid']}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', SaleStatusEnum::Completed->value);

    // Baixa de estoque: 10 - 2 = 8
    expect(stockOf($this->store->uuid, $this->variant->uuid))->toBe(8);

    $this->assertDatabaseHas('inventory_movements', [
        'store_id'       => $this->store->uuid,
        'variant_id'     => $this->variant->uuid,
        'type'           => 'sale',
        'reference_type' => 'sale',
    ]);

    // Contas a receber: pagamento cash (instantâneo) → income/paid
    $receivable = FinancialEntry::where('reference_type', 'sale')
        ->where('reference_id', $sale['uuid'])
        ->first();
    expect($receivable)->not->toBeNull()
        ->and($receivable->type)->toBe(FinancialEntryTypeEnum::Income)
        ->and($receivable->status)->toBe(FinancialEntryStatusEnum::Paid)
        ->and($receivable->amount_cents)->toBe(10000);
});

// ── CENÁRIO 3 — Condicional → Saída Estoque → Conversão Parcial ───────────────
it('cenário 3: condicional → saída de estoque → conversão parcial', function (): void {
    InventoryBalance::factory()->for($this->store, 'store')->for($this->variant, 'variant')
        ->create(['quantity' => 10]);
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->uuid]);

    // Abre condicional de 3 peças → saída de estoque (ConditionalOut)
    $conditional = $this->postJson('/api/v1/conditionals', [
        'store_id'    => $this->store->uuid,
        'customer_id' => $customer->uuid,
        'due_date'    => now()->addDays(7)->toDateString(),
        'items'       => [['variant_id' => $this->variant->uuid, 'quantity' => 3, 'unit_price_cents' => 10000]],
    ])->assertCreated()->json('data');

    expect(stockOf($this->store->uuid, $this->variant->uuid))->toBe(7); // 10 - 3

    // Converte parcialmente 1 de 3
    $itemUuid = $conditional['items'][0]['uuid'];
    $this->postJson("/api/v1/conditionals/{$conditional['uuid']}/convert", [
        'conversions' => [['item_uuid' => $itemUuid, 'quantity' => 1]],
    ])->assertOk()
      ->assertJsonPath('data.status', ConditionalStatusEnum::PartiallyConverted->value);

    // Conversão NÃO debita estoque de novo (já saiu na abertura)
    expect(stockOf($this->store->uuid, $this->variant->uuid))->toBe(7);
});

// ── CENÁRIO 4 — Cancelamento Venda → Retorno Estoque → Estorno Financeiro ──────
it('cenário 4: cancelamento de venda → retorno de estoque → estorno financeiro', function (): void {
    InventoryBalance::factory()->for($this->store, 'store')->for($this->variant, 'variant')
        ->create(['quantity' => 10]);
    $session = CashRegisterSession::factory()->open()->create(['store_id' => $this->store->uuid]);

    $sale = $this->postJson('/api/v1/sales', [
        'store_id'   => $this->store->uuid,
        'session_id' => $session->uuid,
        'items'      => [[
            'variant_id'       => $this->variant->uuid,
            'sku_snapshot'     => 'SKU-1',
            'name_snapshot'    => 'Produto',
            'quantity'         => 2,
            'unit_price_cents' => 5000,
        ]],
    ])->assertCreated()->json('data');

    $this->postJson("/api/v1/sales/{$sale['uuid']}/payments", [
        'method' => 'cash', 'amount_cents' => 10000,
    ])->assertCreated();
    $this->postJson("/api/v1/sales/{$sale['uuid']}/complete")->assertOk();

    expect(stockOf($this->store->uuid, $this->variant->uuid))->toBe(8); // baixou

    // Cancela a venda
    $this->postJson("/api/v1/sales/{$sale['uuid']}/cancel", [
        'reason' => 'Cliente desistiu',
    ])->assertOk()
      ->assertJsonPath('data.status', SaleStatusEnum::Cancelled->value);

    // Retorno de estoque: volta para 10
    expect(stockOf($this->store->uuid, $this->variant->uuid))->toBe(10);

    // Movimento de retorno registrado
    $this->assertDatabaseHas('inventory_movements', [
        'store_id'       => $this->store->uuid,
        'variant_id'     => $this->variant->uuid,
        'type'           => 'return',
        'reference_type' => 'sale',
    ]);

    // Estorno financeiro: o título da venda foi cancelado
    $entry = FinancialEntry::where('reference_type', 'sale')
        ->where('reference_id', $sale['uuid'])
        ->first();
    expect($entry)->not->toBeNull()
        ->and($entry->status)->toBe(FinancialEntryStatusEnum::Cancelled);
});

// ── CENÁRIO 5 — Transferência de Estoque → Loja Origem → Loja Destino ──────────
it('cenário 5: transferência de estoque entre lojas (origem → destino)', function (): void {
    $destination = Store::factory()->create(['name' => 'Destino']);
    InventoryBalance::factory()->for($this->store, 'store')->for($this->variant, 'variant')
        ->create(['quantity' => 50]);

    $transfer = $this->postJson('/api/v1/inventory/transfers', [
        'origin_store_id'      => $this->store->uuid,
        'destination_store_id' => $destination->uuid,
        'items'                => [['variant_id' => $this->variant->uuid, 'quantity' => 10]],
    ])->assertCreated()->json('data');

    // Despacha (saída da origem)
    $this->postJson("/api/v1/inventory/transfers/{$transfer['uuid']}/dispatch", [
        'items' => [['variant_id' => $this->variant->uuid, 'quantity_sent' => 10]],
    ])->assertOk();

    expect(stockOf($this->store->uuid, $this->variant->uuid))->toBe(40); // 50 - 10

    // Recebe no destino (entrada)
    $this->postJson("/api/v1/inventory/transfers/{$transfer['uuid']}/receive", [
        'items' => [['variant_id' => $this->variant->uuid, 'quantity_received' => 10]],
    ])->assertOk();

    expect(stockOf($destination->uuid, $this->variant->uuid))->toBe(10);
    expect(stockOf($this->store->uuid, $this->variant->uuid))->toBe(40);
});
