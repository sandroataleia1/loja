<?php

declare(strict_types=1);

use App\Modules\Inventory\Models\Store;
use App\Modules\Sync\Events\DeviceRegistered;
use App\Modules\Sync\Models\SyncDevice;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    $this->store = Store::factory()->create();
});

describe('device_registration — registro de dispositivos PDV', function (): void {
    it('registra novo dispositivo com sucesso', function (): void {
        Event::fake([DeviceRegistered::class]);

        $deviceUuid = Str::uuid()->toString();

        $this->postJson('/api/v1/sync/devices/register', [
            'store_id'    => $this->store->uuid,
            'device_uuid' => $deviceUuid,
            'name'        => 'Caixa 01 - Loja Centro',
            'platform'    => 'windows',
            'app_version' => '1.0.0',
        ])->assertStatus(201)
            ->assertJsonPath('data.device_uuid', $deviceUuid)
            ->assertJsonPath('data.name', 'Caixa 01 - Loja Centro')
            ->assertJsonPath('data.platform', 'windows')
            ->assertJsonPath('data.is_active', true);

        Event::assertDispatched(DeviceRegistered::class);

        $this->assertDatabaseHas('sync_devices', [
            'tenant_id'   => $this->tenant->uuid,
            'store_id'    => $this->store->uuid,
            'device_uuid' => $deviceUuid,
        ]);
    });

    it('re-registro com mesmo device_uuid retorna dispositivo existente sem duplicata', function (): void {
        Event::fake([DeviceRegistered::class]);

        $deviceUuid = Str::uuid()->toString();

        $payload = [
            'store_id'    => $this->store->uuid,
            'device_uuid' => $deviceUuid,
            'name'        => 'Caixa 01',
            'platform'    => 'windows',
            'app_version' => '1.0.0',
        ];

        $this->postJson('/api/v1/sync/devices/register', $payload)->assertStatus(201);
        $this->postJson('/api/v1/sync/devices/register', array_merge($payload, [
            'name'        => 'Caixa 01 - Atualizado',
            'app_version' => '1.1.0',
        ]))->assertStatus(201)
            ->assertJsonPath('data.name', 'Caixa 01 - Atualizado')
            ->assertJsonPath('data.app_version', '1.1.0');

        Event::assertDispatchedTimes(DeviceRegistered::class, 1);
        expect(SyncDevice::where('device_uuid', $deviceUuid)->count())->toBe(1);
    });

    it('reativa dispositivo deletado no re-registro', function (): void {
        // Fake apenas o evento de domínio — Event::fake() sem args suprimiria os
        // eventos de modelo do Eloquent (incl. o creating do HasUuid → uuid null).
        Event::fake([DeviceRegistered::class]);

        $device = SyncDevice::factory()->create([
            'store_id'    => $this->store->uuid,
            'tenant_id'   => $this->tenant->uuid,
            'device_uuid' => $uuid = Str::uuid()->toString(),
            'is_active'   => true,
        ]);
        $device->delete();

        $this->postJson('/api/v1/sync/devices/register', [
            'store_id'    => $this->store->uuid,
            'device_uuid' => $uuid,
            'name'        => 'Caixa Reativada',
            'platform'    => 'android',
        ])->assertStatus(201)
            ->assertJsonPath('data.is_active', true);
    });

    it('rejeita store_id de outro tenant', function (): void {
        // Loja de OUTRO tenant real (stores.tenant_id tem FK para tenants).
        $otherTenant = \App\Core\Tenancy\Models\Tenant::factory()->create();
        $otherStore  = Store::factory()->create(['tenant_id' => $otherTenant->uuid]);

        $this->postJson('/api/v1/sync/devices/register', [
            'store_id'    => $otherStore->uuid,
            'device_uuid' => Str::uuid()->toString(),
            'name'        => 'Caixa Invasora',
        ])->assertStatus(422);
    });

    it('lista dispositivos do tenant', function (): void {
        SyncDevice::factory()->count(3)->create([
            'store_id'  => $this->store->uuid,
            'tenant_id' => $this->tenant->uuid,
        ]);

        // success(Resource::collection($paginator)) achata os itens em `data`.
        $this->getJson('/api/v1/sync/devices')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    it('manager pode desativar dispositivo', function (): void {
        $device = SyncDevice::factory()->create([
            'store_id'  => $this->store->uuid,
            'tenant_id' => $this->tenant->uuid,
        ]);

        $this->patchJson("/api/v1/sync/devices/{$device->uuid}/deactivate")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);
    });

    it('operador não pode desativar dispositivo', function (): void {
        $this->restrictUserToPermissions(); // sem settings.update

        $device = SyncDevice::factory()->create([
            'store_id'  => $this->store->uuid,
            'tenant_id' => $this->tenant->uuid,
        ]);

        $this->patchJson("/api/v1/sync/devices/{$device->uuid}/deactivate")
            ->assertStatus(403);
    });
});
