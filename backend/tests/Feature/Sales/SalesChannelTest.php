<?php

declare(strict_types=1);

use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Enums\SalesChannelEnum;
use App\Modules\Sales\Models\Sale;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    $this->store = Store::factory()->create();
});

describe('sales_channel — origem omnichannel', function (): void {
    it('cria venda com canal PDV por padrão', function (): void {
        $this->postJson('/api/v1/sales', [
            'store_id' => $this->store->uuid,
            'items'    => [[
                'sku_snapshot'     => 'SKU-1',
                'name_snapshot'    => 'Produto',
                'quantity'         => 1,
                'unit_price_cents' => 5000,
            ]],
        ])->assertStatus(201)
            ->assertJsonPath('data.sales_channel', 'pdv')
            ->assertJsonPath('data.sales_channel_label', 'PDV');
    });

    it('registra canal Instagram', function (): void {
        $this->postJson('/api/v1/sales', [
            'store_id'      => $this->store->uuid,
            'sales_channel' => 'instagram',
            'items'         => [[
                'sku_snapshot'     => 'SKU-1',
                'name_snapshot'    => 'Produto',
                'quantity'         => 1,
                'unit_price_cents' => 9900,
            ]],
        ])->assertStatus(201)
            ->assertJsonPath('data.sales_channel', 'instagram')
            ->assertJsonPath('data.sales_channel_label', 'Instagram');

        $this->assertDatabaseHas('sales', [
            'tenant_id'    => $this->tenant->uuid,
            'sales_channel' => SalesChannelEnum::Instagram->value,
        ]);
    });

    it('registra canal WhatsApp', function (): void {
        $this->postJson('/api/v1/sales', [
            'store_id'      => $this->store->uuid,
            'sales_channel' => 'whatsapp',
            'items'         => [[
                'sku_snapshot'     => 'SKU-1',
                'name_snapshot'    => 'Produto',
                'quantity'         => 1,
                'unit_price_cents' => 12000,
            ]],
        ])->assertStatus(201)
            ->assertJsonPath('data.sales_channel', 'whatsapp');
    });

    it('rejeita canal inválido', function (): void {
        $this->postJson('/api/v1/sales', [
            'store_id'      => $this->store->uuid,
            'sales_channel' => 'tiktok',
            'items'         => [[
                'sku_snapshot'     => 'SKU-1',
                'name_snapshot'    => 'Produto',
                'quantity'         => 1,
                'unit_price_cents' => 5000,
            ]],
        ])->assertStatus(422);
    });

    it('vendas existentes recebem canal pdv por retrocompatibilidade', function (): void {
        $sale = Sale::factory()->create([
            'store_id' => $this->store->uuid,
        ]);

        // sales_channel column defaults to 'pdv' in migration
        expect($sale->fresh()->sales_channel)->toBe(SalesChannelEnum::Pdv);
    });

    it('SalesChannelEnum isDigital retorna corretamente', function (): void {
        expect(SalesChannelEnum::Pdv->isDigital())->toBeFalse();
        expect(SalesChannelEnum::Instagram->isDigital())->toBeTrue();
        expect(SalesChannelEnum::Whatsapp->isDigital())->toBeTrue();
        expect(SalesChannelEnum::Ecommerce->isDigital())->toBeTrue();
        expect(SalesChannelEnum::Marketplace->isDigital())->toBeTrue();
    });
});
