<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Jobs;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Models\PriceList;
use App\Modules\Catalog\Models\ProductPrice;
use App\Modules\Catalog\Models\ProductPriceHistory;
use App\Modules\Catalog\Models\Variant;
use App\Modules\Catalog\Services\PriceResolverService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;

/**
 * Processa importação de preços via CSV.
 *
 * Formato CSV:
 * price_list_code, variant_sku, price_cents, min_price_cents,
 * packaging_price_cents, packaging_qty, valid_from, valid_to, reason
 */
final class ImportPricesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries   = 1;
    public int $timeout = 600;

    public function __construct(
        private readonly string $filePath,
        private readonly string $priceListId,
        private readonly string $tenantId,
        private readonly string $userId,
    ) {
        $this->onQueue('catalog');
    }

    public function handle(PriceResolverService $resolver): void
    {
        TenantContext::set($this->tenantId);

        $fullPath  = Storage::disk('local')->path($this->filePath);
        $rows      = $this->readCsv($fullPath);
        $priceList = PriceList::find($this->priceListId);

        if ($priceList === null) {
            Log::error('ImportPricesJob: price list not found', ['price_list_id' => $this->priceListId]);
            return;
        }

        $success = 0;
        $errors  = [];

        foreach ($rows->chunk(100) as $chunk) {
            DB::transaction(function () use ($chunk, $priceList, &$success, &$errors, $resolver): void {
                foreach ($chunk as $index => $row) {
                    try {
                        $this->processRow($row, $priceList, $resolver);
                        $success++;
                    } catch (\Throwable $e) {
                        $errors[] = ['line' => $index + 2, 'error' => $e->getMessage(), 'row' => $row];
                    }
                }
            });
        }

        Log::info('ImportPricesJob completed', [
            'price_list_id' => $this->priceListId,
            'tenant_id'     => $this->tenantId,
            'success'       => $success,
            'errors'        => count($errors),
        ]);

        Storage::disk('local')->delete($this->filePath);
    }

    private function processRow(array $row, PriceList $priceList, PriceResolverService $resolver): void
    {
        $sku = trim((string) ($row['variant_sku'] ?? ''));

        if ($sku === '') {
            throw new \InvalidArgumentException('variant_sku é obrigatório.');
        }

        $variant = Variant::where('tenant_id', $this->tenantId)
            ->where('sku', $sku)
            ->first(['uuid', 'product_id']);

        if ($variant === null) {
            throw new \InvalidArgumentException("Variante com SKU '{$sku}' não encontrada.");
        }

        $priceCents = (int) ($row['price_cents'] ?? 0);

        if ($priceCents <= 0) {
            throw new \InvalidArgumentException("price_cents deve ser maior que zero. SKU: {$sku}");
        }

        $key = [
            'price_list_id' => $priceList->uuid,
            'variant_id'    => $variant->uuid,
            'product_id'    => null,
        ];

        $fill = array_merge($key, [
            'tenant_id'             => $this->tenantId,
            'price_cents'           => $priceCents,
            'min_price_cents'       => isset($row['min_price_cents']) && $row['min_price_cents'] !== '' ? (int) $row['min_price_cents'] : null,
            'packaging_price_cents' => isset($row['packaging_price_cents']) && $row['packaging_price_cents'] !== '' ? (int) $row['packaging_price_cents'] : null,
            'packaging_qty'         => isset($row['packaging_qty']) && $row['packaging_qty'] !== '' ? (float) $row['packaging_qty'] : null,
            'valid_from'            => isset($row['valid_from']) && $row['valid_from'] !== '' ? $row['valid_from'] : null,
            'valid_to'              => isset($row['valid_to']) && $row['valid_to'] !== '' ? $row['valid_to'] : null,
        ]);

        $existing = ProductPrice::where($key)->first();
        $oldPrice = $existing?->price_cents;

        ProductPrice::updateOrCreate($key, $fill);

        if ($oldPrice !== $priceCents) {
            ProductPriceHistory::create([
                'tenant_id'       => $this->tenantId,
                'product_id'      => $variant->product_id,
                'variant_id'      => $variant->uuid,
                'old_price_cents' => $oldPrice,
                'new_price_cents' => $priceCents,
                'changed_by'      => $this->userId,
                'changed_at'      => now(),
                'reason'          => isset($row['reason']) && $row['reason'] !== '' ? $row['reason'] : ($oldPrice === null ? 'Importação CSV — cadastro inicial' : 'Importação CSV'),
            ]);
        }

        $resolver->invalidate($this->tenantId, $priceList->uuid, $variant->uuid);
    }

    private function readCsv(string $path): LazyCollection
    {
        return LazyCollection::make(function () use ($path): \Generator {
            $handle = fopen($path, 'r');

            if ($handle === false) {
                throw new \RuntimeException("Não foi possível abrir o arquivo: {$path}");
            }

            $headers = fgetcsv($handle, 0, ',');

            if ($headers === false) {
                fclose($handle);
                return;
            }

            $headers = array_map('trim', $headers);

            while (($line = fgetcsv($handle, 0, ',')) !== false) {
                if (count($line) !== count($headers)) {
                    continue;
                }

                yield array_combine($headers, array_map('trim', $line));
            }

            fclose($handle);
        });
    }
}
