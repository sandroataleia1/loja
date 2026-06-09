<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Data;

/**
 * Dados de um item do documento fiscal.
 */
final readonly class FiscalItemData
{
    public function __construct(
        public ?string $variantUuid,
        public string  $description,
        public int     $quantity,
        public int     $unitPriceCents,
        public int     $totalCents,
        public array   $taxData = [],
    ) {}
}
