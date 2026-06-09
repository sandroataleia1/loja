<?php

declare(strict_types=1);

use App\Modules\Finance\Enums\FinancialEntryStatusEnum;
use App\Modules\Finance\Enums\FinancialEntryTypeEnum;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\Store;
use App\Modules\Catalog\Models\Variant;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\Supplier;

beforeEach(function (): void {
    $this->actingAsTenantUser();

    $this->store   = Store::factory()->create();
    $this->variant = Variant::factory()->create();
});

// ── Suppliers ─────────────────────────────────────────────────────────────────

describe('Suppliers', function (): void {
    it('cria fornecedor com código automático (201)', function (): void {
        $r = $this->postJson('/api/v1/purchasing/suppliers', [
            'person_type' => 'COMPANY',
            'name'        => 'Fornecedor Teste LTDA',
            'document'    => '12.345.678/0001-90',
            'email'       => 'contato@fornecedor.com',
        ])->assertCreated()
          ->assertJsonPath('success', true)
          ->assertJsonPath('data.name', 'Fornecedor Teste LTDA');

        expect($r->json('data.code'))->not->toBeNull()->toStartWith('FOR');
    });

    it('lista fornecedores do tenant (200)', function (): void {
        Supplier::factory()->count(3)->create(['tenant_id' => $this->tenant->uuid]);

        $this->getJson('/api/v1/purchasing/suppliers')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    });

    it('não vaza fornecedores de outros tenants', function (): void {
        $other = \App\Core\Tenancy\Models\Tenant::factory()->create();
        Supplier::factory()->create(['tenant_id' => $other->uuid]);
        Supplier::factory()->count(2)->create(['tenant_id' => $this->tenant->uuid]);

        $this->getJson('/api/v1/purchasing/suppliers')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('atualiza fornecedor (200)', function (): void {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $this->putJson("/api/v1/purchasing/suppliers/{$supplier->uuid}", ['name' => 'Nome Atualizado'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nome Atualizado');
    });
});

// ── Purchase Orders ───────────────────────────────────────────────────────────

describe('Purchase Orders', function (): void {
    it('cria pedido de compra com código automático (201)', function (): void {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $r = $this->postJson('/api/v1/purchasing/orders', [
            'supplier_id' => $supplier->uuid,
            'store_id'    => $this->store->uuid,
            'order_date'  => today()->toDateString(),
            'items'       => [
                ['product_variant_id' => $this->variant->uuid, 'quantity' => 10, 'unit_cost' => 25.50],
            ],
        ])->assertCreated()
          ->assertJsonPath('success', true)
          ->assertJsonPath('data.status', 'draft');

        expect($r->json('data.code'))->toStartWith('CPR');
        expect($r->json('data.total'))->toBe('255.00');
    });

    it('calcula totais corretamente', function (): void {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $v2       = Variant::factory()->create();

        $this->postJson('/api/v1/purchasing/orders', [
            'supplier_id' => $supplier->uuid,
            'store_id'    => $this->store->uuid,
            'order_date'  => today()->toDateString(),
            'discount'    => 10,
            'items'       => [
                ['product_variant_id' => $this->variant->uuid, 'quantity' => 5,  'unit_cost' => 20.00],
                ['product_variant_id' => $v2->uuid,            'quantity' => 3,  'unit_cost' => 30.00],
            ],
        ])->assertCreated()
          ->assertJsonPath('data.subtotal', '190.00') // 100 + 90
          ->assertJsonPath('data.total', '180.00');   // 190 - 10
    });

    it('envia pedido (draft → sent)', function (): void {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $order = PurchaseOrder::factory()->create([
            'tenant_id'   => $this->tenant->uuid,
            'supplier_id' => $supplier->uuid,
            'store_id'    => $this->store->uuid,
            'status'      => 'draft',
        ]);

        $this->postJson("/api/v1/purchasing/orders/{$order->uuid}/send")
            ->assertOk()
            ->assertJsonPath('data.status', 'sent');
    });

    it('cancela pedido (draft ou sent)', function (): void {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $order = PurchaseOrder::factory()->create([
            'tenant_id'   => $this->tenant->uuid,
            'supplier_id' => $supplier->uuid,
            'store_id'    => $this->store->uuid,
            'status'      => 'draft',
        ]);

        $this->postJson("/api/v1/purchasing/orders/{$order->uuid}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    });
});

// ── Receipts & Inventory ──────────────────────────────────────────────────────

describe('Purchase Receipts', function (): void {
    it('recebe mercadoria e gera entrada de estoque', function (): void {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $order    = $this->postJson('/api/v1/purchasing/orders', [
            'supplier_id' => $supplier->uuid,
            'store_id'    => $this->store->uuid,
            'order_date'  => today()->toDateString(),
            'items'       => [['product_variant_id' => $this->variant->uuid, 'quantity' => 20, 'unit_cost' => 10.00]],
        ])->json('data');

        // Send order first
        $this->postJson("/api/v1/purchasing/orders/{$order['uuid']}/send");
        $itemUuid = $order['items'][0]['uuid'];

        // Receive
        $this->postJson("/api/v1/purchasing/orders/{$order['uuid']}/receive", [
            'items' => [['order_item_uuid' => $itemUuid, 'quantity_received' => 20]],
        ])->assertCreated()
          ->assertJsonPath('success', true);

        // Check inventory updated
        $balance = InventoryBalance::where('store_id', $this->store->uuid)
            ->where('variant_id', $this->variant->uuid)
            ->first();

        expect($balance)->not->toBeNull()
            ->and($balance->quantity)->toBe(20);

        // Check inventory movement created
        $this->assertDatabaseHas('inventory_movements', [
            'store_id'       => $this->store->uuid,
            'variant_id'     => $this->variant->uuid,
            'type'           => 'in',
            'quantity'       => 20,
            'reference_type' => 'purchase_receipt',
        ]);
    });

    it('recebimento de compra gera título a pagar (A9-01)', function (): void {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $order    = $this->postJson('/api/v1/purchasing/orders', [
            'supplier_id' => $supplier->uuid,
            'store_id'    => $this->store->uuid,
            'order_date'  => today()->toDateString(),
            'items'       => [['product_variant_id' => $this->variant->uuid, 'quantity' => 20, 'unit_cost' => 10.00]],
        ])->json('data');

        $this->postJson("/api/v1/purchasing/orders/{$order['uuid']}/send");
        $itemUuid = $order['items'][0]['uuid'];

        $receipt = $this->postJson("/api/v1/purchasing/orders/{$order['uuid']}/receive", [
            'items' => [['order_item_uuid' => $itemUuid, 'quantity_received' => 20]],
        ])->assertCreated()->json('data');

        // 20 × R$10,00 = R$200,00 → 20000 centavos, como despesa pendente.
        $payable = FinancialEntry::where('reference_type', 'purchase_receipt')
            ->where('reference_id', $receipt['uuid'])
            ->first();

        expect($payable)->not->toBeNull()
            ->and($payable->type)->toBe(FinancialEntryTypeEnum::Expense)
            ->and($payable->status)->toBe(FinancialEntryStatusEnum::Pending)
            ->and($payable->amount_cents)->toBe(20000)
            ->and($payable->store_id)->toBe($this->store->uuid);
    });

    it('recebe parcialmente (PARTIALLY_RECEIVED)', function (): void {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $order    = $this->postJson('/api/v1/purchasing/orders', [
            'supplier_id' => $supplier->uuid,
            'store_id'    => $this->store->uuid,
            'order_date'  => today()->toDateString(),
            'items'       => [['product_variant_id' => $this->variant->uuid, 'quantity' => 100, 'unit_cost' => 5.00]],
        ])->json('data');

        $this->postJson("/api/v1/purchasing/orders/{$order['uuid']}/send");
        $itemUuid = $order['items'][0]['uuid'];

        // Receive only 40 of 100
        $this->postJson("/api/v1/purchasing/orders/{$order['uuid']}/receive", [
            'items' => [['order_item_uuid' => $itemUuid, 'quantity_received' => 40]],
        ])->assertCreated();

        // Order should be PARTIALLY_RECEIVED
        $this->getJson("/api/v1/purchasing/orders/{$order['uuid']}")
            ->assertOk()
            ->assertJsonPath('data.status', 'partially_received');

        // Pending = 60
        $this->assertDatabaseHas('purchase_order_items', [
            'uuid'              => $itemUuid,
            'quantity'          => 100,
            'received_quantity' => 40,
        ]);
    });

    it('não permite receber quantidade maior que o pendente', function (): void {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $order    = $this->postJson('/api/v1/purchasing/orders', [
            'supplier_id' => $supplier->uuid,
            'store_id'    => $this->store->uuid,
            'order_date'  => today()->toDateString(),
            'items'       => [['product_variant_id' => $this->variant->uuid, 'quantity' => 10, 'unit_cost' => 10.00]],
        ])->json('data');

        $this->postJson("/api/v1/purchasing/orders/{$order['uuid']}/send");
        $itemUuid = $order['items'][0]['uuid'];

        // Try to receive 99 (more than 10)
        $this->postJson("/api/v1/purchasing/orders/{$order['uuid']}/receive", [
            'items' => [['order_item_uuid' => $itemUuid, 'quantity_received' => 99]],
        ])->assertUnprocessable();
    });

    it('acumula recebimentos múltiplos (partial + partial = full)', function (): void {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $order    = $this->postJson('/api/v1/purchasing/orders', [
            'supplier_id' => $supplier->uuid,
            'store_id'    => $this->store->uuid,
            'order_date'  => today()->toDateString(),
            'items'       => [['product_variant_id' => $this->variant->uuid, 'quantity' => 50, 'unit_cost' => 2.00]],
        ])->json('data');

        $this->postJson("/api/v1/purchasing/orders/{$order['uuid']}/send");
        $itemUuid = $order['items'][0]['uuid'];

        $this->postJson("/api/v1/purchasing/orders/{$order['uuid']}/receive", [
            'items' => [['order_item_uuid' => $itemUuid, 'quantity_received' => 30]],
        ])->assertCreated();

        $this->postJson("/api/v1/purchasing/orders/{$order['uuid']}/receive", [
            'items' => [['order_item_uuid' => $itemUuid, 'quantity_received' => 20]],
        ])->assertCreated();

        $this->getJson("/api/v1/purchasing/orders/{$order['uuid']}")
            ->assertOk()
            ->assertJsonPath('data.status', 'received');

        // Total stock = 50
        $balance = InventoryBalance::where('store_id', $this->store->uuid)->where('variant_id', $this->variant->uuid)->first();
        expect($balance->quantity)->toBe(50);
    });
});
