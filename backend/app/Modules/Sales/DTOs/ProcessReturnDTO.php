<?php

declare(strict_types=1);

namespace App\Modules\Sales\DTOs;

use App\Modules\Sales\Enums\ReturnReasonEnum;
use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class ProcessReturnDTO extends BaseDTO
{
    /**
     * @param ProcessReturnItemDTO[] $items
     */
    public function __construct(
        public string            $originalSaleId,
        public ReturnReasonEnum  $reason,
        public string            $returnType,      // return | exchange
        public array             $items,
        public ?string           $refundMethod,
        public ?string           $notes,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            originalSaleId: $request->string('original_sale_id')->toString(),
            reason:         ReturnReasonEnum::from($request->string('reason')->toString()),
            returnType:     $request->string('return_type', 'return')->toString(),
            items:          array_map(
                fn (array $i) => ProcessReturnItemDTO::fromArray($i),
                $request->array('items'),
            ),
            refundMethod: $request->string('refund_method')->value() ?: null,
            notes:        $request->string('notes')->value() ?: null,
        );
    }
}
