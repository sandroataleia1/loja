<?php

declare(strict_types=1);

namespace App\Modules\Inventory\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class CreateStockCountDTO extends BaseDTO
{
    /**
     * @param string[]|null $variantIds — null = all variants in store
     */
    public function __construct(
        public string  $storeId,
        public ?array  $variantIds,
        public ?string $notes,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            storeId:    $request->string('store_id')->toString(),
            variantIds: $request->has('variant_ids') ? $request->array('variant_ids') : null,
            notes:      $request->string('notes')->value() ?: null,
        );
    }
}
