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
        public ?string $sellerPin        = null,
        public ?string $quoteId          = null,
        public string  $discountType     = 'fixed',
        public float   $discountValue    = 0,
        public ?string $notes            = null,
        public ?string $internalNotes    = null,
        public ?string $paymentTerms     = null,
        public ?string $expectedAt       = null,
        public array   $items            = [],
        public ?string $paymentMethodId    = null,
        public ?string $paymentConditionId = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $items = collect($request->array('items'))
            ->values()
            ->map(fn (array $item, int $i) => DocumentItemDTO::fromArray($item, $i))
            ->all();

        return new static(
            storeId:            $request->string('store_id')->value() ?: null,
            customerId:         $request->string('customer_id')->value() ?: null,
            sellerPin:          $request->string('seller_pin')->value() ?: null,
            quoteId:            $request->string('quote_id')->value() ?: null,
            discountType:       $request->string('discount_type', 'fixed')->toString(),
            discountValue:      (float) $request->input('discount_value', 0),
            notes:              $request->string('notes')->value() ?: null,
            internalNotes:      $request->string('internal_notes')->value() ?: null,
            paymentTerms:       $request->string('payment_terms')->value() ?: null,
            expectedAt:         $request->string('expected_at')->value() ?: null,
            items:              $items,
            paymentMethodId:    $request->string('payment_method_id')->value() ?: null,
            paymentConditionId: $request->string('payment_condition_id')->value() ?: null,
        );
    }
}
