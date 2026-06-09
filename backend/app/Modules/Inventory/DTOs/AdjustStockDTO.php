<?php

declare(strict_types=1);

namespace App\Modules\Inventory\DTOs;

use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class AdjustStockDTO extends BaseDTO
{
    public function __construct(
        public string          $storeId,
        public string          $variantId,
        public int             $quantity,
        public MovementTypeEnum $type,
        public ?string         $notes,
        public ?string         $referenceType,
        public ?string         $referenceId,
        public ?array          $metadata,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            storeId:       $request->string('store_id')->toString(),
            variantId:     $request->string('variant_id')->toString(),
            quantity:      $request->integer('quantity'),
            type:          MovementTypeEnum::from($request->string('type', 'adjustment')->toString()),
            notes:         $request->string('notes')->value() ?: null,
            referenceType: $request->string('reference_type')->value() ?: null,
            referenceId:   $request->string('reference_id')->value() ?: null,
            metadata:      $request->array('metadata') ?: null,
        );
    }
}
