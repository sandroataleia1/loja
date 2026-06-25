<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Jobs;

use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Actions\UpdateProductAction;
use App\Modules\Catalog\DTOs\CreateProductDTO;
use App\Modules\Catalog\Enums\ProductStatusEnum;
use App\Modules\Catalog\Enums\ProductTypeEnum;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\CatalogBarcode;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\NcmCode;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Unit;
use App\Modules\Catalog\Models\Variant;
use App\Core\Tenancy\Services\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;

final class ImportProductsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries    = 1;
    public int $timeout  = 600;

    public function __construct(
        private readonly string $filePath,
        private readonly string $tenantId,
        private readonly string $userId,
    ) {
        $this->onQueue('catalog');
    }

    public function handle(
        CreateProductAction $createAction,
        UpdateProductAction $updateAction,
    ): void {
        TenantContext::set($this->tenantId);

        $fullPath = Storage::disk('local')->path($this->filePath);
        $rows     = $this->readFile($fullPath);

        $success = 0;
        $errors  = [];

        foreach ($rows->chunk(50) as $chunk) {
            foreach ($chunk as $lineNumber => $row) {
                try {
                    $this->processRow($row, $createAction, $updateAction);
                    $success++;
                } catch (\Throwable $e) {
                    $errors[] = ['line' => $lineNumber + 2, 'error' => $e->getMessage(), 'row' => $row];
                    Log::warning("ImportProducts line {$lineNumber}: {$e->getMessage()}", ['row' => $row]);
                }
            }
        }

        Storage::disk('local')->delete($this->filePath);

        Log::info("ImportProducts completed: {$success} success, " . count($errors) . ' errors', [
            'tenant_id' => $this->tenantId,
            'errors'    => $errors,
        ]);
    }

    /** @param array<string, mixed> $row */
    private function processRow(
        array               $row,
        CreateProductAction $createAction,
        UpdateProductAction $updateAction,
    ): void {
        $tenantId = $this->tenantId;

        // Resolve FK simples
        $brand    = $row['brand_code'] ? Brand::where('tenant_id', $tenantId)->where('code', $row['brand_code'])->first() : null;
        $category = $row['category_code'] ? Category::where('tenant_id', $tenantId)->where('code', $row['category_code'])->first() : null;
        $unit     = $row['unit_code'] ? Unit::where('code', $row['unit_code'])->first() : null;
        $ncm      = $row['ncm_code'] ? NcmCode::where('code', $row['ncm_code'])->first() : null;

        $existing = Product::where('tenant_id', $tenantId)->where('code', $row['code'])->first();

        if ($existing === null) {
            $dto = new CreateProductDTO(
                name:                 (string) $row['name'],
                slug:                 Str::slug((string) $row['name']),
                type:                 ProductTypeEnum::tryFrom((string) ($row['type'] ?? 'simple')) ?? ProductTypeEnum::Simple,
                unitOfMeasure:        null,
                ncm:                  $ncm?->code ?? ($row['ncm_code'] ?: null),
                cest:                 null,
                cfopDefault:          null,
                originCode:           null,
                status:               ProductStatusEnum::tryFrom((string) ($row['status'] ?? 'draft')) ?? ProductStatusEnum::Draft,
                brandId:              $brand?->uuid,
                categoryUuids:        $category !== null ? [$category->uuid] : null,
                collectionId:         null,
                gridId:               null,
                unitId:               $unit?->uuid,
                basePriceCents:       isset($row['price_cents']) ? (int) $row['price_cents'] : null,
                costPriceCents:       isset($row['cost_cents']) ? (int) $row['cost_cents'] : null,
                description:          null,
                shortDescription:     null,
                marketingDescription: null,
                internalNotes:        null,
                isFeatured:           false,
                isDigital:            false,
                isPublishable:        false,
                season:               null,
                launchDate:           null,
                seo:                  null,
            );

            $product = $createAction->execute($dto);
        } else {
            $product = $existing;
        }

        // Cria variante se variant_sku presente
        if (! empty($row['variant_sku'])) {
            $variant = Variant::firstOrCreate(
                ['tenant_id' => $tenantId, 'sku' => $row['variant_sku']],
                [
                    'product_id'  => $product->uuid,
                    'name'        => $row['name'],
                    'barcode'     => $row['variant_barcode'] ?: null,
                    'price_cents' => isset($row['price_cents']) ? (int) $row['price_cents'] : 0,
                    'cost_cents'  => isset($row['cost_cents']) ? (int) $row['cost_cents'] : null,
                    'weight_g'    => isset($row['weight_g']) ? (int) $row['weight_g'] : null,
                    'is_active'   => true,
                    'is_default'  => true,
                ],
            );

            // Migra código de barras para tabela dedicada
            if (! empty($row['barcode_value'])) {
                $barcodeType = $row['barcode_type'] ?: 'ean13';
                CatalogBarcode::firstOrCreate(
                    ['tenant_id' => $tenantId, 'value' => $row['barcode_value']],
                    ['variant_id' => $variant->uuid, 'barcode_type' => $barcodeType, 'is_primary' => true],
                );
            }
        }
    }

    private function readFile(string $path): LazyCollection
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv'  => $this->readCsv($path),
            'xlsx' => $this->readXlsx($path),
            default => throw new \InvalidArgumentException("Formato não suportado: {$extension}"),
        };
    }

    private function readCsv(string $path): LazyCollection
    {
        return LazyCollection::make(function () use ($path): \Generator {
            $handle  = fopen($path, 'r');
            $headers = null;

            while (($line = fgetcsv($handle, 0, ',')) !== false) {
                if (str_starts_with((string) ($line[0] ?? ''), '#')) {
                    continue; // linha de comentário/exemplo
                }

                if ($headers === null) {
                    $headers = $line;
                    continue;
                }

                yield array_combine($headers, $line);
            }

            fclose($handle);
        });
    }

    private function readXlsx(string $path): LazyCollection
    {
        // Requer PhpSpreadsheet (phpoffice/phpspreadsheet)
        if (! class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \RuntimeException('phpoffice/phpspreadsheet é necessário para importação XLSX.');
        }

        return LazyCollection::make(function () use ($path): \Generator {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $sheet       = $spreadsheet->getActiveSheet();
            $rows        = $sheet->toArray();
            $headers     = array_shift($rows);

            foreach ($rows as $row) {
                yield array_combine($headers, $row);
            }
        });
    }
}
