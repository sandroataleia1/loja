<?php

declare(strict_types=1);

namespace App\Modules\Orders\Actions;

use App\Core\Auth\Models\User;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Orders\DTOs\CreateQuoteDTO;
use App\Modules\Orders\DTOs\DocumentItemDTO;
use App\Modules\Orders\Models\Quote;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class CreateQuoteAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
    ) {}

    public function execute(CreateQuoteDTO $dto): Quote
    {
        return DB::transaction(function () use ($dto): Quote {
            $tenantId = TenantContext::getIdOrFail();

            $number = $this->generateCode->execute(
                tenantId: $tenantId,
                entity:   SequenceEntityEnum::Quote,
            );

            $discountCents = $dto->discountType === 'fixed'
                ? (int) round($dto->discountValue * 100)
                : 0;

            $sellerId = null;
            if ($dto->sellerPin) {
                $seller   = User::where('tenant_id', $tenantId)
                    ->where('pin', $dto->sellerPin)
                    ->where('is_active', true)
                    ->first();
                $sellerId = $seller?->uuid;
            }

            $quote = Quote::create([
                'tenant_id'            => $tenantId,
                'store_id'             => $dto->storeId,
                'customer_id'          => $dto->customerId,
                'payment_method_id'    => $dto->paymentMethodId,
                'payment_condition_id' => $dto->paymentConditionId,
                'seller_id'            => $sellerId,
                'number'               => $number,
                'status'               => 'draft',
                'validity_days'        => $dto->validityDays,
                'valid_until'          => Carbon::today()->addDays($dto->validityDays),
                'discount_type'        => $dto->discountType,
                'discount_value'       => $dto->discountValue,
                'discount_cents'       => $discountCents,
                'notes'                => $dto->notes,
                'internal_notes'       => $dto->internalNotes,
                'payment_terms'        => $dto->paymentTerms,
            ]);

            foreach ($dto->items as $i => $item) {
                $this->createItem($quote, $item, $i);
            }

            $quote->recalculateTotals();

            return $quote->load('items', 'seller');
        });
    }

    private function createItem(Quote $quote, DocumentItemDTO $item, int $index): void
    {
        $quote->items()->create([
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
