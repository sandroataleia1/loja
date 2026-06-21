<?php

declare(strict_types=1);

namespace App\Modules\Orders\DTOs;

use Illuminate\Http\Request;

final readonly class CreateOrderDTO
{
    /** @param DocumentItemDTO[] $items */
    public function __construct(
        public ?string $storeId,
        public ?string $customerId,
        public ?string $quoteId,
        public string  $discountType,
        public float   $discountValue,
        public ?string $notes,
        public ?string $internalNotes,
        public ?string $paymentTerms,
        public ?string $expectedAt,
        public array   $items,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $items = collect($request->array('items'))
            ->values()
            ->map(fn (array $item, int $i) => DocumentItemDTO::fromArray($item, $i))
            ->all();

        return new static(
            storeId:       $request->string('store_id')->value() ?: null,
            customerId:    $request->string('customer_id')->value() ?: null,
            quoteId:       $request->string('quote_id')->value() ?: null,
            discountType:  $request->string('discount_type', 'fixed')->toString(),
            discountValue: (float) $request->input('discount_value', 0),
            notes:         $request->string('notes')->value() ?: null,
            internalNotes: $request->string('internal_notes')->value() ?: null,
            paymentTerms:  $request->string('payment_terms')->value() ?: null,
            expectedAt:    $request->string('expected_at')->value() ?: null,
            items:         $items,
        );
    }
}
