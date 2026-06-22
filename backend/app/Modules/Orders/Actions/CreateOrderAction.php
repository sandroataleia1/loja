<?php

declare(strict_types=1);

namespace App\Modules\Orders\Actions;

use App\Core\Auth\Models\User;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Orders\DTOs\CreateOrderDTO;
use App\Modules\Orders\DTOs\DocumentItemDTO;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\DiscountApprovalService;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use Illuminate\Support\Facades\DB;

final readonly class CreateOrderAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
        private DiscountApprovalService    $discountApproval,
    ) {}

    public function execute(CreateOrderDTO $dto): Order
    {
        return DB::transaction(function () use ($dto): Order {
            $tenantId = TenantContext::getIdOrFail();

            $number = $this->generateCode->execute(
                tenantId: $tenantId,
                entity:   SequenceEntityEnum::Order,
            );

            $discountCents = $dto->discountType === 'fixed'
                ? (int) round($dto->discountValue * 100)
                : 0;

            $sellerId = null;
            if ($dto->sellerPin) {
                $hashedPin = hash('sha256', config('app.key', '') . $dto->sellerPin);
                $seller    = User::where('tenant_id', $tenantId)
                    ->where('pin', $hashedPin)
                    ->where('is_active', true)
                    ->first();
                $sellerId  = $seller?->uuid;
            }

            $subtotalCents = array_sum(array_map(fn (DocumentItemDTO $i): int => $i->subtotalCents, $dto->items));
            $this->discountApproval->enforce(
                sellerUuid:    $sellerId,
                discountType:  $dto->discountType,
                discountValue: $dto->discountValue,
                subtotalCents: $subtotalCents,
            );

            $order = Order::create([
                'tenant_id'            => $tenantId,
                'store_id'             => $dto->storeId,
                'customer_id'          => $dto->customerId,
                'payment_method_id'    => $dto->paymentMethodId,
                'payment_condition_id' => $dto->paymentConditionId,
                'seller_id'            => $sellerId,
                'quote_id'             => $dto->quoteId,
                'number'               => $number,
                'status'               => 'pending',
                'discount_type'        => $dto->discountType,
                'discount_value'       => $dto->discountValue,
                'discount_cents'       => $discountCents,
                'notes'                => $dto->notes,
                'internal_notes'       => $dto->internalNotes,
                'payment_terms'        => $dto->paymentTerms,
                'expected_at'          => $dto->expectedAt,
            ]);

            foreach ($dto->items as $i => $item) {
                $this->createItem($order, $item, $i);
            }

            $order->recalculateTotals();

            return $order->load('items', 'seller');
        });
    }

    private function createItem(Order $order, DocumentItemDTO $item, int $index): void
    {
        $order->items()->create([
            'product_variant_id' => $item->productVariantId,
            'name_snapshot'      => $item->nameSnapshot,
            'sku_snapshot'       => $item->skuSnapshot,
            'unit_of_measure'    => $item->unitOfMeasure,
            'quantity'           => $item->quantity,
            'unit_price_cents'   => $item->unitPriceCents,
            'discount_cents'     => $item->discountCents,
            'subtotal_cents'     => $item->subtotalCents,
            'sort_order'         => $item->sortOrder !== 0 ? $item->sortOrder : $index,
            'notes'              => $item->notes,
        ]);
    }
}
