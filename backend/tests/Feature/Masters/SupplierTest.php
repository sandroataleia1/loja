<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Modules\Purchasing\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('GET /purchasing/suppliers', function (): void {
    it('lista fornecedores do tenant', function (): void {
        Supplier::factory()->count(3)->create(['tenant_id' => $this->tenant->uuid]);

        $this->getJson('/api/v1/purchasing/suppliers')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    });

    it('não retorna fornecedores de outro tenant', function (): void {
        $other = Tenant::factory()->create();
        Supplier::factory()->count(2)->create(['tenant_id' => $other->uuid]);
        Supplier::factory()->count(1)->create(['tenant_id' => $this->tenant->uuid]);

        $this->getJson('/api/v1/purchasing/suppliers')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('POST /purchasing/suppliers', function (): void {
    it('cria um fornecedor com CNPJ válido', function (): void {
        $response = $this->postJson('/api/v1/purchasing/suppliers', [
            'person_type' => 'COMPANY',
            'name'        => 'Fornecedor Teste Ltda',
            'document'    => '11.222.333/0001-81',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Fornecedor Teste Ltda');

        $this->assertDatabaseHas('suppliers', ['name' => 'Fornecedor Teste Ltda', 'tenant_id' => $this->tenant->uuid]);
    });

    it('rejeita CNPJ inválido para pessoa jurídica', function (): void {
        $this->postJson('/api/v1/purchasing/suppliers', [
            'person_type' => 'COMPANY',
            'name'        => 'Fornecedor Inválido',
            'document'    => '00.000.000/0000-00',
        ])->assertUnprocessable();
    });

    it('rejeita nome em branco', function (): void {
        $this->postJson('/api/v1/purchasing/suppliers', [
            'person_type' => 'COMPANY',
            'name'        => '',
        ])->assertUnprocessable();
    });
});

describe('PUT /purchasing/suppliers/{supplier}', function (): void {
    it('atualiza nome do fornecedor', function (): void {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $this->putJson("/api/v1/purchasing/suppliers/{$supplier->uuid}", ['name' => 'Novo Nome Ltda'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Novo Nome Ltda');
    });
});

describe('DELETE /purchasing/suppliers/{supplier}', function (): void {
    it('faz soft delete do fornecedor', function (): void {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $this->deleteJson("/api/v1/purchasing/suppliers/{$supplier->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('suppliers', ['uuid' => $supplier->uuid]);
    });
});

describe('suspend/reactivate', function (): void {
    it('suspende um fornecedor', function (): void {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $supplier->suspend('Inadimplência');

        $supplier->refresh();

        expect($supplier->status->value)->toBe('suspended')
            ->and($supplier->suspension_reason)->toBe('Inadimplência');
    });

    it('reativa um fornecedor suspenso', function (): void {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $supplier->suspend('Teste');
        $supplier->reactivate();

        $supplier->refresh();
        expect($supplier->status->value)->toBe('active')
            ->and($supplier->suspension_reason)->toBeNull();
    });
});

describe('isolamento de tenant', function (): void {
    it('não permite acessar fornecedor de outro tenant', function (): void {
        $other    = Tenant::factory()->create();
        $supplier = Supplier::factory()->create(['tenant_id' => $other->uuid]);

        $this->getJson("/api/v1/purchasing/suppliers/{$supplier->uuid}")
            ->assertNotFound();
    });
});

describe('POST /purchasing/suppliers/import', function (): void {
    it('aceita upload de CSV válido e retorna 202', function (): void {
        Queue::fake();
        Storage::fake('local');

        $csv  = "name,person_type,document\nFornecedor SA,COMPANY,11.222.333/0001-81\n";
        $file = UploadedFile::fake()->createWithContent('fornecedores.csv', $csv);

        $this->postJson('/api/v1/purchasing/suppliers/import', ['file' => $file])
            ->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['import_log_id']]);
    });
});
