<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Modules\Customers\Models\Customer;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('Consumidor padrão — criação automática', function (): void {
    it('cria consumidor padrão automaticamente ao criar tenant', function (): void {
        // actingAsTenantUser() já cria um tenant → TenantCreated → listener
        $this->assertDatabaseHas('customers', [
            'tenant_id'           => $this->tenant->uuid,
            'name'                => 'Consumidor Final',
            'is_default_consumer' => true,
        ]);
    });

    it('consumidor padrão tem CPF nulo', function (): void {
        $consumer = Customer::withoutTenantScope()
            ->where('tenant_id', $this->tenant->uuid)
            ->where('is_default_consumer', true)
            ->first();

        expect($consumer)->not->toBeNull()
            ->and($consumer->cpf)->toBeNull();
    });

    it('existe exatamente um consumidor padrão por tenant', function (): void {
        $count = Customer::withoutTenantScope()
            ->where('tenant_id', $this->tenant->uuid)
            ->where('is_default_consumer', true)
            ->count();

        expect($count)->toBe(1);
    });

    it('consumidor padrão tem código CLI gerado', function (): void {
        $consumer = Customer::withoutTenantScope()
            ->where('tenant_id', $this->tenant->uuid)
            ->where('is_default_consumer', true)
            ->first();

        expect($consumer->code)->toStartWith('CLI');
    });

    it('tenants diferentes têm consumidores padrão independentes', function (): void {
        $tenant2 = Tenant::factory()->create();

        $consumer1 = Customer::withoutTenantScope()
            ->where('tenant_id', $this->tenant->uuid)
            ->where('is_default_consumer', true)
            ->first();

        $consumer2 = Customer::withoutTenantScope()
            ->where('tenant_id', $tenant2->uuid)
            ->where('is_default_consumer', true)
            ->first();

        expect($consumer1)->not->toBeNull()
            ->and($consumer2)->not->toBeNull()
            ->and($consumer1->uuid)->not->toBe($consumer2->uuid);
    });
});

describe('GET /customers — listagem oculta consumidor padrão', function (): void {
    it('não retorna consumidor padrão na listagem normal', function (): void {
        Customer::factory()->count(3)->create();

        $this->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('retorna consumidor padrão com ?include_default=1', function (): void {
        Customer::factory()->count(3)->create();

        $this->getJson('/api/v1/customers?include_default=1')
            ->assertOk()
            ->assertJsonCount(4, 'data'); // 3 normais + 1 padrão
    });
});

describe('DELETE /customers — proteção do consumidor padrão', function (): void {
    it('impede exclusão do consumidor padrão', function (): void {
        $consumer = Customer::withoutTenantScope()
            ->where('tenant_id', $this->tenant->uuid)
            ->where('is_default_consumer', true)
            ->first();

        $this->deleteJson("/api/v1/customers/{$consumer->uuid}")
            ->assertStatus(403);
    });

    it('permite excluir cliente normal', function (): void {
        $customer = Customer::factory()->create();

        $this->deleteJson("/api/v1/customers/{$customer->uuid}")
            ->assertStatus(204);

        $this->assertSoftDeleted('customers', ['uuid' => $customer->uuid]);
    });
});

describe('PUT /customers — proteção de is_default_consumer', function (): void {
    it('operador não pode modificar consumidor padrão', function (): void {
        $consumer = Customer::withoutTenantScope()
            ->where('tenant_id', $this->tenant->uuid)
            ->where('is_default_consumer', true)
            ->first();

        $this->putJson("/api/v1/customers/{$consumer->uuid}", [
            'name' => 'Outro Nome',
        ])->assertStatus(403);
    });

    it('pode atualizar cliente normal', function (): void {
        $customer = Customer::factory()->create(['name' => 'João Silva']);

        $this->putJson("/api/v1/customers/{$customer->uuid}", [
            'name'  => 'João Santos',
            'email' => 'joao@example.com',
        ])->assertOk()
            ->assertJsonPath('data.name', 'João Santos');
    });
});

describe('POST /customers — criação com código automático', function (): void {
    it('cria cliente com código CLI sequencial', function (): void {
        // CLI000001 já foi para o consumidor padrão
        $this->postJson('/api/v1/customers', [
            'person_type' => 'INDIVIDUAL',
            'name'        => 'Maria Oliveira',
        ])->assertStatus(201)
            ->assertJsonPath('data.code', 'CLI000002');
    });

    it('rejeita documento duplicado dentro do tenant', function (): void {
        Customer::factory()->withDocument('12345678900')->create();

        $this->postJson('/api/v1/customers', [
            'person_type' => 'INDIVIDUAL',
            'name'        => 'Outro Cliente',
            'document'    => '12345678900',
        ])->assertStatus(409);
    });
});
