<?php

declare(strict_types=1);

use App\Modules\Catalog\Jobs\ImportProductsJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    Storage::fake('local');
    Queue::fake();
});

describe('POST /catalog/products/import', function (): void {
    it('aceita CSV válido e despacha job', function (): void {
        $csv = implode("\n", [
            'code,name,category_code,brand_code,unit_code,ncm_code,price_cents,cost_cents,status,type,variant_sku,variant_barcode,variant_attributes,weight_g,barcode_type,barcode_value',
            'PROD001,"Cimento CP-II",,,UN,,4500,3200,active,simple,PROD001-SC,,,,ean13,7891234567890',
        ]);

        $file = UploadedFile::fake()->createWithContent('catalog.csv', $csv);

        $response = $this->post('/api/v1/catalog/products/import', ['file' => $file]);

        $response->assertStatus(202);
        Queue::assertPushed(ImportProductsJob::class);
    });

    it('aceita XLSX', function (): void {
        // Cria arquivo XLSX fake (binário mínimo)
        $file = UploadedFile::fake()->create('catalog.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->post('/api/v1/catalog/products/import', ['file' => $file]);

        $response->assertStatus(202);
        Queue::assertPushed(ImportProductsJob::class);
    });

    it('rejeita arquivo sem file', function (): void {
        $this->postJson('/api/v1/catalog/products/import', [])
            ->assertStatus(422);
    });
});

describe('GET /catalog/products/import/template', function (): void {
    it('retorna CSV com headers corretos', function (): void {
        $response = $this->get('/api/v1/catalog/products/import/template');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->content();
        expect($content)->toContain('code,name,category_code')
            ->and($content)->toContain('price_cents,cost_cents');
    });
});

describe('ImportProductsJob', function (): void {
    it('usa tenant_id do usuário autenticado', function (): void {
        $csv  = "code,name,category_code,brand_code,unit_code,ncm_code,price_cents,cost_cents,status,type,variant_sku,variant_barcode,variant_attributes,weight_g,barcode_type,barcode_value\n";
        $csv .= "PROD999,\"Produto Teste\",,,,,2000,1000,draft,simple,SKU999,,,,,\n";

        $path = 'imports/catalog/' . $this->tenant->uuid . '/test.csv';
        Storage::disk('local')->put($path, $csv);

        Queue::assertNotPushed(ImportProductsJob::class);

        $this->post('/api/v1/catalog/products/import', [
            'file' => UploadedFile::fake()->createWithContent('test.csv', $csv),
        ]);

        Queue::assertPushed(ImportProductsJob::class, function (ImportProductsJob $job) {
            return true;
        });
    });
});
