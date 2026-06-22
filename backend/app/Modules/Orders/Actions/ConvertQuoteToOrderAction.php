<?php

declare(strict_types=1);

namespace App\Modules\Orders\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Orders\DTOs\CreateOrderDTO;
use App\Modules\Orders\DTOs\DocumentItemDTO;
use App\Modules\Orders\Enums\QuoteStatusEnum;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Quote;
use App\Shared\Exceptions\ConflictException;
use Illuminate\Support\Facades\DB;

final readonly class ConvertQuoteToOrderAction
{
    public function __construct(
        private CreateOrderAction $createOrder,
    ) {}

    public function execute(Quote $quote, ?string $expectedAt = null): Order
    {
        if (! $quote->status->canConvert()) {
            throw new ConflictException(
                "Orçamento {$quote->number} não pode ser convertido — status: {$quote->status->label()}."
            );
        }

        return DB::transaction(function () use ($quote, $expectedAt): Order {
            // Copia itens do orçamento para o DTO de criação de pedido
            $items = $quote->items->map(fn ($qi) => new DocumentItemDTO(
                productVariantId: $qi->product_variant_id,
                nameSnapshot:     $qi->name_snapshot,
                skuSnapshot:      $qi->sku_snapshot,
                unitOfMeasure:    $qi->unit_of_measure,
                quantity:         (float) $qi->quantity,
                unitPriceCents:   $qi->unit_price_cents,
                discountCents:    $qi->discount_cents,
                subtotalCents:    $qi->subtotal_cents,
                sortOrder:        $qi->sort_order,
                notes:            $qi->notes,
            ))->all();

            $dto = new CreateOrderDTO(
                storeId:            $quote->store_id,
                customerId:         $quote->customer_id,
                sellerPin:          null,
                quoteId:            $quote->uuid,
                discountType:       $quote->discount_type,
                discountValue:      (float) $quote->discount_value,
                notes:              $quote->notes,
                internalNotes:      $quote->internal_notes,
                paymentTerms:       $quote->payment_terms,
                expectedAt:         $expectedAt,
                items:              $items,
                paymentMethodId:    $quote->payment_method_id,
                paymentConditionId: $quote->payment_condition_id,
            );

            $order = $this->createOrder->execute($dto);

            // Marca o orçamento como convertido
            $quote->update([
                'status'                => QuoteStatusEnum::Converted,
                'converted_to_order_id' => $order->uuid,
                'converted_at'          => now(),
            ]);

            return $order;
        });
    }
}
