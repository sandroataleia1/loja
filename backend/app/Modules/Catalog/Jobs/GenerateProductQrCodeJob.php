<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Jobs;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\QrCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class GenerateProductQrCodeJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly string $productUuid,
    ) {
        $this->onQueue('catalog');
    }

    public function handle(QrCodeService $service): void
    {
        $product = Product::where('uuid', $this->productUuid)->first();

        if ($product === null) {
            return;
        }

        $service->generateForProduct($product);

        // Gera QR Code para cada variante também
        foreach ($product->variants as $variant) {
            $service->generateForVariant($variant);
        }
    }
}
