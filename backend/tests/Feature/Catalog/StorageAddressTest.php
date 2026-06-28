<?php

declare(strict_types=1);

use App\Core\Features\FeatureEnum;
use App\Core\Tenancy\Models\TenantFeature;
use App\Modules\Catalog\Models\StorageAddress;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('Endereçamento de Estoque', function (): void {
    it('feature desativada retorna 403', function (): void {
        $response = $this->getJson('/api/v1/catalog/storage-addresses');

        $response->assertStatus(403);
    });

    it('cria endereço e fullAddress retorna aisle-rack-shelf-position', function (): void {
        TenantFeature::create([
            'tenant_id'  => $this->tenant->uuid,
            'feature'    => FeatureEnum::InventoryAddress->value,
            'is_enabled' => true,
        ]);

        $response = $this->postJson('/api/v1/catalog/storage-addresses', [
            'aisle'    => 'A',
            'rack'     => '01',
            'shelf'    => 'B',
            'position' => '03',
        ]);

        $response->assertStatus(201);

        $address = StorageAddress::where('tenant_id', $this->tenant->uuid)->first();
        expect($address)->not->toBeNull()
            ->and($address->full_address)->toBe('A-01-B-03');
    });

    it('fullAddress omite partes nulas', function (): void {
        $address = StorageAddress::make([
            'aisle'    => 'A',
            'rack'     => '01',
            'shelf'    => null,
            'position' => null,
        ]);

        expect($address->full_address)->toBe('A-01');
    });

    it('endereço duplicado retorna 422', function (): void {
        TenantFeature::create([
            'tenant_id'  => $this->tenant->uuid,
            'feature'    => FeatureEnum::InventoryAddress->value,
            'is_enabled' => true,
        ]);

        $payload = [
            'aisle'    => 'A',
            'rack'     => '01',
            'shelf'    => 'B',
            'position' => '03',
        ];

        $this->postJson('/api/v1/catalog/storage-addresses', $payload)->assertStatus(201);
        $this->postJson('/api/v1/catalog/storage-addresses', $payload)->assertStatus(422);
    });

    it('lista endereços do tenant', function (): void {
        TenantFeature::create([
            'tenant_id'  => $this->tenant->uuid,
            'feature'    => FeatureEnum::InventoryAddress->value,
            'is_enabled' => true,
        ]);

        StorageAddress::factory()->count(3)->create(['tenant_id' => $this->tenant->uuid]);

        $response = $this->getJson('/api/v1/catalog/storage-addresses');

        $response->assertOk()->assertJsonCount(3, 'data');
    });
});
