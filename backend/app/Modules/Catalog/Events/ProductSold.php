<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Events;

use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Disparado quando um produto é vendido (SaleCompleted).
 *
 * IA futura consumirá para:
 * - Calcular sales_velocity
 * - Atualizar last_sale_at
 * - Alimentar motor de recomendações
 * - Detectar tendências e sazonalidade
 */
final class ProductSold
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly int     $quantitySold,
        public readonly string  $saleUuid,
        public readonly string  $salesChannel,
    ) {}
}
