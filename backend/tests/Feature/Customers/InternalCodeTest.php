<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Models\TenantSequence;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Variant;
use App\Modules\Customers\Models\Customer;
use App\Modules\Sales\Models\Sale;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('GenerateInternalCodeAction', function (): void {
    it('gera código PRO000001 para o primeiro produto', function (): void {
        $action = app(GenerateInternalCodeAction::class);

        $code = $action->execute($this->tenant->uuid, SequenceEntityEnum::Product);

        expect($code)->toBe('PRO000001');
    });

    it('incrementa sequência a cada chamada', function (): void {
        $action = app(GenerateInternalCodeAction::class);

        $code1 = $action->execute($this->tenant->uuid, SequenceEntityEnum::Product);
        $code2 = $action->execute($this->tenant->uuid, SequenceEntityEnum::Product);
        $code3 = $action->execute($this->tenant->uuid, SequenceEntityEnum::Product);

        expect($code1)->toBe('PRO000001')
            ->and($code2)->toBe('PRO000002')
            ->and($code3)->toBe('PRO000003');
    });

    it('sequências são isoladas por tenant', function (): void {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $action = app(GenerateInternalCodeAction::class);

        TenantContext::set($tenant1->uuid);
        $code1 = $action->execute($tenant1->uuid, SequenceEntityEnum::Sale);

        TenantContext::set($tenant2->uuid);
        $code2 = $action->execute($tenant2->uuid, SequenceEntityEnum::Sale);

        expect($code1)->toBe('VEN000001')
            ->and($code2)->toBe('VEN000001'); // ambos começam em 1
    });

    it('sequências são isoladas por entidade', function (): void {
        $action = app(GenerateInternalCodeAction::class);

        $prodCode = $action->execute($this->tenant->uuid, SequenceEntityEnum::Product);
        $saleCode = $action->execute($this->tenant->uuid, SequenceEntityEnum::Sale);
        $cusCode  = $action->execute($this->tenant->uuid, SequenceEntityEnum::Customer);

        expect($prodCode)->toBe('PRO000001')
            ->and($saleCode)->toBe('VEN000001')
            ->and($cusCode)->toBe('CLI000002'); // CUS000001 foi o consumidor padrão
    });

    it('cria registro TenantSequence na primeira chamada', function (): void {
        $action = app(GenerateInternalCodeAction::class);
        $action->execute($this->tenant->uuid, SequenceEntityEnum::Transfer);

        $this->assertDatabaseHas('tenant_sequences', [
            'tenant_id'     => $this->tenant->uuid,
            'entity'        => SequenceEntityEnum::Transfer->value,
            'sub_entity_id' => '',
            'current_value' => 1,
        ]);
    });
});

describe('Código de variante derivado do produto', function (): void {
    it('gera PRO000001-001 para primeira variante', function (): void {
        $product = Product::factory()->create(['code' => 'PRO000001']);

        $this->postJson('/api/v1/catalog/variants', [
            'product_id'  => $product->uuid,
            'sku'         => 'SKU-001',
            'price_cents' => 9999,
        ])->assertStatus(201)
            ->assertJsonPath('data.code', 'PRO000001-001');
    });

    it('gera PRO000001-002 para segunda variante do mesmo produto', function (): void {
        $product = Product::factory()->create(['code' => 'PRO000001']);

        $this->postJson('/api/v1/catalog/variants', [
            'product_id'  => $product->uuid,
            'sku'         => 'SKU-001',
            'price_cents' => 9999,
        ])->assertStatus(201);

        $this->postJson('/api/v1/catalog/variants', [
            'product_id'  => $product->uuid,
            'sku'         => 'SKU-002',
            'price_cents' => 9999,
        ])->assertStatus(201)
            ->assertJsonPath('data.code', 'PRO000001-002');
    });

    it('sequências de variantes são independentes por produto', function (): void {
        $product1 = Product::factory()->create(['code' => 'PRO000001']);
        $product2 = Product::factory()->create(['code' => 'PRO000002']);

        $this->postJson('/api/v1/catalog/variants', [
            'product_id'  => $product1->uuid,
            'sku'         => 'SKU-A1',
            'price_cents' => 5000,
        ])->assertStatus(201)
            ->assertJsonPath('data.code', 'PRO000001-001');

        $this->postJson('/api/v1/catalog/variants', [
            'product_id'  => $product2->uuid,
            'sku'         => 'SKU-B1',
            'price_cents' => 5000,
        ])->assertStatus(201)
            ->assertJsonPath('data.code', 'PRO000002-001');
    });
});

describe('Código gerado na criação via API', function (): void {
    it('produto criado via API recebe código PRO', function (): void {
        $this->postJson('/api/v1/catalog/products', [
            'name' => 'Tijolo Cerâmico',
            'type' => 'simple',
        ])->assertStatus(201)
            ->assertJsonPath('data.code', 'PRO000001');
    });

    it('venda criada via API recebe código VEN', function (): void {
        $store   = \App\Modules\Inventory\Models\Store::factory()->create();
        $variant = Variant::factory()->create();

        $this->postJson('/api/v1/sales', [
            'store_id' => $store->uuid,
            'items'    => [[
                'variant_id'       => $variant->uuid,
                'sku_snapshot'     => $variant->sku,
                'name_snapshot'    => 'Produto Teste',
                'quantity'         => 1,
                'unit_price_cents' => 1000,
            ]],
        ])->assertStatus(201)
            ->assertJsonPath('data.code', 'VEN000001');
    });

    it('código de produto incrementa corretamente entre tenants', function (): void {
        $this->postJson('/api/v1/catalog/products', ['name' => 'Produto A', 'type' => 'simple'])
            ->assertStatus(201)
            ->assertJsonPath('data.code', 'PRO000001');

        // Troca de tenant
        $outerTenant = Tenant::factory()->create();
        $this->actingAsTenantUser($outerTenant);

        $this->postJson('/api/v1/catalog/products', ['name' => 'Produto B', 'type' => 'simple'])
            ->assertStatus(201)
            ->assertJsonPath('data.code', 'PRO000001'); // começa em 1 para novo tenant
    });
});
