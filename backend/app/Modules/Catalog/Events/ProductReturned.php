<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Events;

use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Disparado quando um produto é devolvido (SaleRefunded).
 *
 * IA futura usará para ajustar:
 * - Taxa de retorno por produto
 * - Score de qualidade do catálogo
 * - Decisões de campanha (não promover produtos com alta taxa de devolução)
 */
final class ProductReturned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly int     $quantityReturned,
        public readonly string  $originalSaleUuid,
    ) {}
}
