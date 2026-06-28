<?php

declare(strict_types=1);

use App\Modules\Catalog\Jobs\ExportCatalogPdfJob;
use App\Modules\Catalog\Models\Product;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('Exportação Catálogo', function (): void {
    it('POST export/pdf despacha job e retorna 202', function (): void {
        Queue::fake();

        $response = $this->postJson('/api/v1/catalog/catalog/export/pdf', [
            'include_prices' => true,
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonStructure(['data' => ['job_id', 'status']]);

        Queue::assertPushed(ExportCatalogPdfJob::class);
    });

    it('POST export/pdf aceita collection_id opcional', function (): void {
        Queue::fake();

        $response = $this->postJson('/api/v1/catalog/catalog/export/pdf', [
            'collection_id'  => \Illuminate\Support\Str::uuid()->toString(),
            'include_prices' => false,
        ]);

        $response->assertStatus(202);
        Queue::assertPushed(ExportCatalogPdfJob::class);
    });

    it('GET export/csv retorna StreamedResponse com cabeçalho correto', function (): void {
        Product::factory()->count(2)->create(['tenant_id' => $this->tenant->uuid]);

        $response = $this->get('/api/v1/catalog/catalog/products/export/csv');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    });

    it('GET export/csv contém linhas de produto', function (): void {
        Product::factory()->create([
            'tenant_id' => $this->tenant->uuid,
            'name'      => 'Produto Teste CSV',
        ]);

        $response = $this->get('/api/v1/catalog/catalog/products/export/csv');

        $response->assertOk();
        $content = $response->streamedContent();
        expect($content)->toContain('product_uuid');
    });

    it('GET export/{jobId}/status retorna 404 para job desconhecido', function (): void {
        $response = $this->getJson('/api/v1/catalog/catalog/export/job-inexistente/status');

        $response->assertStatus(404);
    });
});
