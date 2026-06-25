<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Modules\Customers\Models\Customer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('GET /customers', function (): void {
    it('lista clientes do tenant', function (): void {
        Customer::factory()->count(3)->create(['tenant_id' => $this->tenant->uuid]);

        $this->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    });

    it('não retorna clientes de outro tenant', function (): void {
        $other = Tenant::factory()->create();
        Customer::factory()->count(2)->create(['tenant_id' => $other->uuid]);
        Customer::factory()->count(1)->create(['tenant_id' => $this->tenant->uuid]);

        $this->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('exclui consumidor final por padrão', function (): void {
        // O actingAsTenantUser já cria o Consumidor Final via evento TenantCreated
        Customer::factory()->count(2)->create(['tenant_id' => $this->tenant->uuid]);

        $this->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });
});

describe('POST /customers', function (): void {
    it('cria um cliente pessoa física com CPF válido', function (): void {
        $response = $this->postJson('/api/v1/customers', [
            'person_type' => 'INDIVIDUAL',
            'name'        => 'João da Silva',
            'document'    => '529.982.247-25',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'João da Silva');
    });

    it('rejeita CPF inválido', function (): void {
        $this->postJson('/api/v1/customers', [
            'person_type' => 'INDIVIDUAL',
            'name'        => 'Teste CPF',
            'document'    => '111.111.111-11',
        ])->assertUnprocessable();
    });

    it('cria cliente pessoa jurídica com CNPJ válido', function (): void {
        $response = $this->postJson('/api/v1/customers', [
            'person_type' => 'COMPANY',
            'name'        => 'Empresa Teste Ltda',
            'document'    => '11.222.333/0001-81',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Empresa Teste Ltda');
    });

    it('rejeita CNPJ inválido para pessoa jurídica', function (): void {
        $this->postJson('/api/v1/customers', [
            'person_type' => 'COMPANY',
            'name'        => 'Empresa Inválida',
            'document'    => '00.000.000/0000-00',
        ])->assertUnprocessable();
    });

    it('rejeita documento duplicado no mesmo tenant', function (): void {
        Customer::factory()->create(['tenant_id' => $this->tenant->uuid, 'document' => '52998224725']);

        $this->postJson('/api/v1/customers', [
            'person_type' => 'INDIVIDUAL',
            'name'        => 'Outro Cliente',
            'document'    => '529.982.247-25',
        ])->assertStatus(409);
    });
});

describe('PUT /customers/{customer}', function (): void {
    it('atualiza nome do cliente', function (): void {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $this->putJson("/api/v1/customers/{$customer->uuid}", ['name' => 'Nome Atualizado'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nome Atualizado');
    });

    it('preserva endereços com UUID em updates', function (): void {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $address  = $customer->addresses()->create([
            'zipcode'    => '01310100',
            'street'     => 'Avenida Paulista',
            'number'     => '1000',
            'district'   => 'Bela Vista',
            'city'       => 'São Paulo',
            'state'      => 'SP',
            'country'    => 'BR',
            'is_default' => true,
        ]);

        $this->putJson("/api/v1/customers/{$customer->uuid}", [
            'addresses' => [[
                'id'         => $address->uuid,
                'zipcode'    => '01310100',
                'street'     => 'Av. Paulista Atualizada',
                'number'     => '2000',
                'district'   => 'Bela Vista',
                'city'       => 'São Paulo',
                'state'      => 'SP',
                'is_default' => true,
            ]],
        ])->assertOk();

        $this->assertDatabaseHas('customer_addresses', ['uuid' => $address->uuid, 'street' => 'Av. Paulista Atualizada']);
    });
});

describe('DELETE /customers/{customer}', function (): void {
    it('faz soft delete do cliente', function (): void {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $this->deleteJson("/api/v1/customers/{$customer->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('customers', ['uuid' => $customer->uuid]);
    });
});

describe('block/unblock', function (): void {
    it('bloqueia um cliente', function (): void {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $customer->block('Inadimplência');

        $customer->refresh();

        expect($customer->status->value)->toBe('blocked')
            ->and($customer->blocked_reason)->toBe('Inadimplência');
    });

    it('desbloqueia um cliente bloqueado', function (): void {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $customer->block('Teste');
        $customer->unblock();

        $customer->refresh();
        expect($customer->blocked_reason)->toBeNull();
    });
});

describe('isolamento de tenant', function (): void {
    it('não permite acessar cliente de outro tenant', function (): void {
        $other    = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $other->uuid]);

        $this->getJson("/api/v1/customers/{$customer->uuid}")
            ->assertNotFound();
    });
});

describe('POST /customers/import', function (): void {
    it('aceita upload de CSV válido e retorna 202', function (): void {
        Queue::fake();
        Storage::fake('local');

        $csv = "name,person_type,document\nJoão Silva,INDIVIDUAL,529.982.247-25\n";
        $file = UploadedFile::fake()->createWithContent('clientes.csv', $csv);

        $this->postJson('/api/v1/customers/import', ['file' => $file])
            ->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['import_log_id']]);
    });

    it('rejeita arquivo maior que 5MB', function (): void {
        $file = UploadedFile::fake()->create('clientes.csv', 6000, 'text/csv');

        $this->postJson('/api/v1/customers/import', ['file' => $file])
            ->assertUnprocessable();
    });

    it('registra erros de linhas inválidas no import_log', function (): void {
        Storage::fake('local');

        // Linha 2: nome vazio (inválido); linha 3: válida
        $csv  = "name,person_type,document\n,INDIVIDUAL,\nMaria Válida,INDIVIDUAL,\n";
        $file = UploadedFile::fake()->createWithContent('clientes.csv', $csv);

        $response = $this->postJson('/api/v1/customers/import', ['file' => $file])
            ->assertStatus(202);

        $importLogId = $response->json('data.import_log_id');

        // A queue é sync nos testes — job já rodou, verifica o import_log
        $this->assertDatabaseHas('import_logs', [
            'uuid'        => $importLogId,
            'error_count' => 1,
            'success_count' => 1,
        ]);
    });
});
