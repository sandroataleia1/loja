<?php

declare(strict_types=1);

namespace App\Modules\Sales\DTOs;

use App\Shared\DTOs\BaseDTO;

final readonly class ProcessReturnItemDTO extends BaseDTO
{
    public function __construct(
        public string  $saleItemId,
        public int     $quantityReturned,
        public ?string $condition,       // good | damaged | defective
        public ?string $conditionNotes,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            saleItemId:       $data['sale_item_id'],
            quantityReturned: (int) $data['quantity_returned'],
            condition:        $data['condition'] ?? null,
            conditionNotes:   $data['condition_notes'] ?? null,
        );
    }
}
