<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class GenerateVariantsDTO extends BaseDTO
{
    public function __construct(
        public string $productId,
        public string $gridId,
        public int    $basePriceCents,
        public string $skuPrefix,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            productId:      $request->string('product_id')->toString(),
            gridId:         $request->string('grid_id')->toString(),
            basePriceCents: $request->integer('base_price_cents'),
            skuPrefix:      strtoupper(trim($request->string('sku_prefix')->toString())),
        );
    }
}
