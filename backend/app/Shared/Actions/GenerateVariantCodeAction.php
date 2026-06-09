<?php

declare(strict_types=1);

namespace App\Shared\Actions;

use App\Modules\Catalog\Models\Product;
use App\Shared\Enums\SequenceEntityEnum;

/**
 * Gera código de variante derivado do produto pai: PRO000145-001.
 *
 * A sequência de variantes é por produto (sub_entity_id = product.uuid),
 * garantindo que PRO000145-001 e PRO000146-001 existam independentemente.
 */
final readonly class GenerateVariantCodeAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
    ) {}

    public function execute(string $tenantId, Product $product): string
    {
        return $this->generateCode->execute(
            tenantId:       $tenantId,
            entity:         SequenceEntityEnum::ProductVariant,
            subEntityId:    $product->uuid,
            prefixOverride: $product->code . '-',
        );
    }
}
