<?php

declare(strict_types=1);

use App\Modules\Catalog\Jobs\ImportPricesJob;
use App\Modules\Catalog\Models\PriceList;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductPrice;
use App\Modules\Catalog\Models\ProductPriceHistory;
use App\Modules\Catalog\Models\Variant;
use App\Modules\Catalog\Services\PriceResolverService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    Storage::fake('local');
    Queue::fake();
});

// ── Upload + despacho do job ──────────────────────────────────────────────────

describe('POST /price-lists/{id}/import', function (): void {
    it('retorna 202 e despacha ImportPricesJob com CSV válido', function (): void {
        $list = PriceList::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $csv = UploadedFile::fake()->createWithContent(
            'prices.csv',
            "variant_sku,price_cents,min_price_cents,packaging_price_cents,packaging_qty,valid_from,valid_to,reason\nPROD001-SC,4500,,,,,,"
        );

        $this->postJson("/api/v1/catalog/price-lists/{$list->uuid}/import", ['file' => $csv])
            ->assertStatus(202);

        Queue::assertPushed(ImportPricesJob::class);
    });

    it('rejeita arquivo que não é CSV', function (): void {
        $list = PriceList::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $file = UploadedFile::fake()->create('prices.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->postJson("/api/v1/catalog/price-lists/{$list->uuid}/import", ['file' => $file])
            ->assertStatus(422);
    });

    it('GET /price-lists/import/template retorna CSV com headers corretos', function (): void {
        $this->get('/api/v1/catalog/price-lists/import/template')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSeeText('variant_sku');
    });
});

// ── Processamento do job ──────────────────────────────────────────────────────

describe('ImportPricesJob — processamento', function (): void {
    it('atualiza preços a partir do CSV', function (): void {

        $product = Product::factory()->create();
        $variant = Variant::factory()->create([
            'product_id'  => $product->uuid,
            'sku'         => 'PROD001-SC',
            'tenant_id'   => $this->tenant->uuid,
            'price_cents' => 5000,
        ]);
        $list = PriceList::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $csvContent = implode("\n", [
            'variant_sku,price_cents,min_price_cents,packaging_price_cents,packaging_qty,valid_from,valid_to,reason',
            'PROD001-SC,4500,3500,,,,,"Promoção"',
        ]);

        Storage::disk('local')->put("imports/prices/{$this->tenant->uuid}/prices.csv", $csvContent);

        $job = new ImportPricesJob(
            "imports/prices/{$this->tenant->uuid}/prices.csv",
            $list->uuid,
            $this->tenant->uuid,
            $this->user->uuid,
        );

        $job->handle(app(PriceResolverService::class));

        $price = ProductPrice::where('variant_id', $variant->uuid)
            ->where('price_list_id', $list->uuid)
            ->first();

        expect($price)->not->toBeNull();
        expect($price->price_cents)->toBe(4500);
        expect($price->min_price_cents)->toBe(3500);
    });

    it('registra histórico para cada preço alterado pelo CSV', function (): void {

        $product = Product::factory()->create();
        $variant = Variant::factory()->create([
            'product_id'  => $product->uuid,
            'sku'         => 'HIST-SKU',
            'tenant_id'   => $this->tenant->uuid,
            'price_cents' => 5000,
        ]);
        $list = PriceList::factory()->create(['tenant_id' => $this->tenant->uuid]);

        // Cria preço existente antes do import
        ProductPrice::create([
            'tenant_id'     => $this->tenant->uuid,
            'price_list_id' => $list->uuid,
            'variant_id'    => $variant->uuid,
            'price_cents'   => 5000,
        ]);

        $csvContent = implode("\n", [
            'variant_sku,price_cents,min_price_cents,packaging_price_cents,packaging_qty,valid_from,valid_to,reason',
            'HIST-SKU,6000,,,,,,Reajuste anual',
        ]);

        Storage::disk('local')->put("imports/prices/{$this->tenant->uuid}/hist.csv", $csvContent);

        $job = new ImportPricesJob(
            "imports/prices/{$this->tenant->uuid}/hist.csv",
            $list->uuid,
            $this->tenant->uuid,
            $this->user->uuid,
        );

        $job->handle(app(PriceResolverService::class));

        $history = ProductPriceHistory::where('variant_id', $variant->uuid)
            ->orderBy('changed_at', 'desc')
            ->first();

        expect($history)->not->toBeNull();
        expect($history->old_price_cents)->toBe(5000);
        expect($history->new_price_cents)->toBe(6000);
        expect($history->reason)->toBe('Reajuste anual');
    });

    it('invalida cache após importação de preços', function (): void {

        $product = Product::factory()->create();
        $variant = Variant::factory()->create([
            'product_id'  => $product->uuid,
            'sku'         => 'CACHE-SKU',
            'tenant_id'   => $this->tenant->uuid,
            'price_cents' => 8000,
        ]);
        $list     = PriceList::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $resolver = app(PriceResolverService::class);

        // Popula cache
        $before = $resolver->resolve($this->tenant->uuid, $variant->uuid, $list->uuid);
        expect($before)->toBe(8000);

        $csvContent = implode("\n", [
            'variant_sku,price_cents,min_price_cents,packaging_price_cents,packaging_qty,valid_from,valid_to,reason',
            'CACHE-SKU,9500,,,,,,"Novo preço"',
        ]);

        Storage::disk('local')->put("imports/prices/{$this->tenant->uuid}/cache.csv", $csvContent);

        $job = new ImportPricesJob(
            "imports/prices/{$this->tenant->uuid}/cache.csv",
            $list->uuid,
            $this->tenant->uuid,
            $this->user->uuid,
        );

        $job->handle($resolver);

        // Cache invalidado — retorna preço atualizado
        $after = $resolver->resolve($this->tenant->uuid, $variant->uuid, $list->uuid);
        expect($after)->toBe(9500);
    });

    it('ignora linha com SKU inválido e continua processando', function (): void {

        $product = Product::factory()->create();
        $variant = Variant::factory()->create([
            'product_id'  => $product->uuid,
            'sku'         => 'VALIDO-SKU',
            'tenant_id'   => $this->tenant->uuid,
            'price_cents' => 1000,
        ]);
        $list = PriceList::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $csvContent = implode("\n", [
            'variant_sku,price_cents,min_price_cents,packaging_price_cents,packaging_qty,valid_from,valid_to,reason',
            'SKU-INEXISTENTE,9999,,,,,,"Deve falhar"',
            'VALIDO-SKU,1500,,,,,,"Deve processar"',
        ]);

        Storage::disk('local')->put("imports/prices/{$this->tenant->uuid}/mixed.csv", $csvContent);

        $job = new ImportPricesJob(
            "imports/prices/{$this->tenant->uuid}/mixed.csv",
            $list->uuid,
            $this->tenant->uuid,
            $this->user->uuid,
        );

        $job->handle(app(PriceResolverService::class));

        // Preço do SKU válido deve ter sido processado
        $price = ProductPrice::where('variant_id', $variant->uuid)->first();
        expect($price)->not->toBeNull();
        expect($price->price_cents)->toBe(1500);
    });
});
