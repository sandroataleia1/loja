<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Resources;

use App\Modules\Inventory\Http\Resources\StoreResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                 => $this->uuid,
            'code'                 => $this->code,
            'store_id'             => $this->store_id,
            'session_id'           => $this->session_id,
            'customer_id'          => $this->customer_id,
            'seller_id'            => $this->seller_id,
            'status'               => $this->status->value,
            'status_label'         => $this->status->label(),
            'subtotal_cents'       => $this->subtotal_cents,
            'discount_total_cents' => $this->discount_total_cents,
            'tax_total_cents'      => $this->tax_total_cents,
            'total_cents'          => $this->total_cents,
            'paid_amount_cents'    => $this->paidAmountCents(),
            'remaining_cents'      => $this->remainingAmountCents(),
            'sync_uuid'            => $this->sync_uuid,
            'fiscal_status'        => $this->fiscal_status?->value,
            'fiscal_mode'          => $this->fiscal_mode?->value,
            'fiscal_mode_label'    => $this->fiscal_mode?->label(),
            'sales_channel'        => $this->sales_channel?->value ?? 'pdv',
            'sales_channel_label'  => $this->sales_channel?->label() ?? 'PDV',
            'reference_sale_id'    => $this->reference_sale_id,
            'notes'                => $this->notes,
            'completed_at'         => $this->completed_at?->toISOString(),
            'cancelled_at'         => $this->cancelled_at?->toISOString(),
            'store'                => $this->whenLoaded('store',       fn () => new StoreResource($this->store)),
            'items'                => $this->whenLoaded('items',       fn () => SaleItemResource::collection($this->items)),
            'payments'             => $this->whenLoaded('payments',    fn () => PaymentTransactionResource::collection($this->payments)),
            'discounts'            => $this->whenLoaded('discounts',   fn () => SaleDiscountResource::collection($this->discounts)),
            'commissions'          => $this->whenLoaded('commissions', fn () => SaleCommissionResource::collection($this->commissions)),
            'created_at'           => $this->created_at?->toISOString(),
            'updated_at'           => $this->updated_at?->toISOString(),
        ];
    }
}
