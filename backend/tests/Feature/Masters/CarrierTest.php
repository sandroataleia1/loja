<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Modules\Carriers\Enums\DeliveryModeEnum;
use App\Modules\Carriers\Models\Carrier;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('GET /carriers', function (): void {
    it('lista transportadoras do tenant', function (): void {
        Carrier::factory()->count(3)->create(['tenant_id' => $this->tenant->uuid]);

        $this->getJson('/api/v1/carriers')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    });

    it('não retorna transportadoras de outro tenant', function (): void {
        $other = Tenant::factory()->create();
        Carrier::factory()->count(2)->create(['tenant_id' => $other->uuid]);
        Carrier::factory()->count(1)->create(['tenant_id' => $this->tenant->uuid]);

        $this->getJson('/api/v1/carriers')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('POST /carriers', function (): void {
    it('cria uma transportadora', function (): void {
        $response = $this->postJson('/api/v1/carriers', [
            'name'          => 'Transportes Rápidos Ltda',
            'cnpj'          => '11.222.333/0001-81',
            'delivery_mode' => 'own_fleet',
            'rntrc'         => 'RNTRC123456',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Transportes Rápidos Ltda')
            ->assertJsonPath('data.delivery_mode', 'own_fleet');

        $this->assertDatabaseHas('carriers', ['name' => 'Transportes Rápidos Ltda', 'tenant_id' => $this->tenant->uuid]);
    });

    it('rejeita nome em branco', function (): void {
        $this->postJson('/api/v1/carriers', ['name' => ''])
            ->assertUnprocessable();
    });
});

describe('PUT /carriers/{carrier}', function (): void {
    it('atualiza uma transportadora', function (): void {
        $carrier = Carrier::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $this->putJson("/api/v1/carriers/{$carrier->uuid}", ['delivery_mode' => 'motorcycle'])
            ->assertOk()
            ->assertJsonPath('data.delivery_mode', 'motorcycle');
    });
});

describe('DELETE /carriers/{carrier}', function (): void {
    it('faz soft delete da transportadora', function (): void {
        $carrier = Carrier::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $this->deleteJson("/api/v1/carriers/{$carrier->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('carriers', ['uuid' => $carrier->uuid]);
    });

    it('não permite acessar transportadora deletada', function (): void {
        $carrier = Carrier::factory()->create(['tenant_id' => $this->tenant->uuid, 'deleted_at' => now()]);

        $this->getJson("/api/v1/carriers/{$carrier->uuid}")
            ->assertNotFound();
    });
});

describe('isolamento de tenant', function (): void {
    it('não permite acessar transportadora de outro tenant', function (): void {
        $other   = Tenant::factory()->create();
        $carrier = Carrier::factory()->create(['tenant_id' => $other->uuid]);

        $this->getJson("/api/v1/carriers/{$carrier->uuid}")
            ->assertNotFound();
    });
});
