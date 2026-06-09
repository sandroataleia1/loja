<?php

declare(strict_types=1);

namespace App\Modules\Inventory\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class ReserveStockDTO extends BaseDTO
{
    public function __construct(
        public string  $storeId,
        public string  $variantId,
        public int     $quantity,
        public ?string $referenceType,
        public ?string $referenceId,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            storeId:       $request->string('store_id')->toString(),
            variantId:     $request->string('variant_id')->toString(),
            quantity:      $request->integer('quantity'),
            referenceType: $request->string('reference_type')->value() ?: null,
            referenceId:   $request->string('reference_id')->value() ?: null,
        );
    }
}
