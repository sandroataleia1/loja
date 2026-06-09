<?php

declare(strict_types=1);

use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\CustomerTag;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('GET /customers — listagem', function (): void {
    it('retorna lista paginada de clientes (200)', function (): void {
        Customer::factory()->count(3)->create();

        $this->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['meta' => ['current_page', 'per_page', 'total', 'last_page']]);
    });

    it('filtra por nome via ?q=', function (): void {
        Customer::factory()->create(['name' => 'Maria Oliveira']);
        Customer::factory()->create(['name' => 'João Silva']);

        $this->getJson('/api/v1/customers?q=maria')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Maria Oliveira');
    });

    it('filtra por tag', function (): void {
        $tag = CustomerTag::create([
            'tenant_id' => $this->tenant->uuid,
            'name'      => 'VIP',
            'color'     => '#FF0000',
        ]);

        $customerWithTag    = Customer::factory()->create(['name' => 'Cliente VIP']);
        $customerWithoutTag = Customer::factory()->create(['name' => 'Cliente Normal']);

        $customerWithTag->tags()->attach($tag->uuid);

        $this->getJson("/api/v1/customers?tag={$tag->uuid}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Cliente VIP');
    });

    it('clientes deletados não aparecem na listagem', function (): void {
        Customer::factory()->count(2)->create();
        $deleted = Customer::factory()->create();
        $deleted->delete();

        $this->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });
});

describe('POST /customers — criação', function (): void {
    it('cria cliente com addresses e contacts (201)', function (): void {
        $this->postJson('/api/v1/customers', [
            'person_type' => 'INDIVIDUAL',
            'name'        => 'Ana Souza',
            'email'       => 'ana@example.com',
            'addresses'   => [
                [
                    'zipcode'  => '01310-100',
                    'street'   => 'Av. Paulista',
                    'number'   => '1000',
                    'district' => 'Bela Vista',
                    'city'     => 'São Paulo',
                    'state'    => 'SP',
                ],
            ],
            'contacts' => [
                [
                    'type'       => 'PHONE',
                    'value'      => '11999999999',
                    'is_primary' => true,
                ],
            ],
        ])->assertStatus(201)
            ->assertJsonPath('data.name', 'Ana Souza')
            ->assertJsonPath('data.person_type', 'INDIVIDUAL')
            ->assertJsonCount(1, 'data.addresses')
            ->assertJsonCount(1, 'data.contacts');

        $this->assertDatabaseHas('customers', ['name' => 'Ana Souza']);
        $this->assertDatabaseHas('customer_addresses', ['city' => 'São Paulo']);
        $this->assertDatabaseHas('customer_contacts', ['type' => 'PHONE', 'value' => '11999999999']);
    });

    it('não cria cliente com documento duplicado no mesmo tenant (409)', function (): void {
        Customer::factory()->withDocument('12345678900')->create();

        $this->postJson('/api/v1/customers', [
            'person_type' => 'INDIVIDUAL',
            'name'        => 'Outro Cliente',
            'document'    => '12345678900',
        ])->assertStatus(409);
    });

    it('rejeita person_type inválido (422)', function (): void {
        $this->postJson('/api/v1/customers', [
            'person_type' => 'UNKNOWN',
            'name'        => 'Teste',
        ])->assertStatus(422);
    });

    it('rejeita criação sem person_type (422)', function (): void {
        $this->postJson('/api/v1/customers', [
            'name' => 'Sem Tipo',
        ])->assertStatus(422);
    });

    it('cria cliente empresa com trade_name', function (): void {
        $this->postJson('/api/v1/customers', [
            'person_type' => 'COMPANY',
            'name'        => 'Empresa Ltda',
            'trade_name'  => 'Empresa Fantasia',
            'document'    => '12345678000190',
        ])->assertStatus(201)
            ->assertJsonPath('data.person_type', 'COMPANY')
            ->assertJsonPath('data.person_type_label', 'Pessoa Jurídica')
            ->assertJsonPath('data.trade_name', 'Empresa Fantasia');
    });
});

describe('DELETE /customers — exclusão', function (): void {
    it('não permite excluir o consumidor padrão (403)', function (): void {
        $consumer = Customer::withoutTenantScope()
            ->where('tenant_id', $this->tenant->uuid)
            ->where('is_default_consumer', true)
            ->first();

        $this->deleteJson("/api/v1/customers/{$consumer->uuid}")
            ->assertStatus(403);
    });

    it('realiza soft delete em cliente normal (204)', function (): void {
        $customer = Customer::factory()->create();

        $this->deleteJson("/api/v1/customers/{$customer->uuid}")
            ->assertStatus(204);

        $this->assertSoftDeleted('customers', ['uuid' => $customer->uuid]);
    });
});

describe('PUT /customers — atualização', function (): void {
    it('não permite atualizar o consumidor padrão (403)', function (): void {
        $consumer = Customer::withoutTenantScope()
            ->where('tenant_id', $this->tenant->uuid)
            ->where('is_default_consumer', true)
            ->first();

        $this->putJson("/api/v1/customers/{$consumer->uuid}", [
            'name' => 'Outro Nome',
        ])->assertStatus(403);
    });

    it('atualiza cliente normal com sucesso (200)', function (): void {
        $customer = Customer::factory()->create(['name' => 'Carlos Lima']);

        $this->putJson("/api/v1/customers/{$customer->uuid}", [
            'name'  => 'Carlos Santos',
            'email' => 'carlos@example.com',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Carlos Santos')
            ->assertJsonPath('data.email', 'carlos@example.com');
    });
});

describe('Tags — attach / detach', function (): void {
    it('adiciona tag a um cliente', function (): void {
        $customer = Customer::factory()->create();
        $tag      = CustomerTag::create([
            'tenant_id' => $this->tenant->uuid,
            'name'      => 'Fidelidade',
            'color'     => '#00FF00',
        ]);

        $this->postJson("/api/v1/customers/{$customer->uuid}/tags", [
            'tag_id' => $tag->uuid,
        ])->assertOk();

        $this->assertDatabaseHas('customer_tag_assignments', [
            'customer_id' => $customer->uuid,
            'tag_id'      => $tag->uuid,
        ]);
    });

    it('remove tag de um cliente', function (): void {
        $customer = Customer::factory()->create();
        $tag      = CustomerTag::create([
            'tenant_id' => $this->tenant->uuid,
            'name'      => 'Remover',
            'color'     => '#0000FF',
        ]);

        $customer->tags()->attach($tag->uuid);

        $this->deleteJson("/api/v1/customers/{$customer->uuid}/tags/{$tag->uuid}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('customer_tag_assignments', [
            'customer_id' => $customer->uuid,
            'tag_id'      => $tag->uuid,
        ]);
    });
});
