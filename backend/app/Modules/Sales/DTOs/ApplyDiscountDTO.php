<?php

declare(strict_types=1);

namespace App\Modules\Sales\DTOs;

use App\Modules\Sales\Enums\DiscountTypeEnum;
use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class ApplyDiscountDTO extends BaseDTO
{
    public function __construct(
        public DiscountTypeEnum $type,
        public float            $percentage,   // usado quando type=percentage
        public int              $amountCents,  // usado quando type=fixed (ou calculado para percentage)
        public ?string          $saleItemId,   // null = desconto na venda; preenchido = no item
        public ?string          $reason,
        public ?string          $approvedBy,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            type:       DiscountTypeEnum::from($request->string('type')->toString()),
            percentage: (float) $request->input('percentage', 0),
            amountCents: $request->integer('amount_cents', 0),
            saleItemId: $request->string('sale_item_id')->value() ?: null,
            reason:     $request->string('reason')->value() ?: null,
            approvedBy: $request->string('approved_by')->value() ?: null,
        );
    }
}
