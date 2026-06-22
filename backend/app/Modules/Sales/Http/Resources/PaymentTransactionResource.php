<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PaymentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                 => $this->uuid,
            'payment_method_id'    => $this->payment_method_id,
            'payment_condition_id' => $this->payment_condition_id,
            'method'               => $this->method->value,
            'method_label'         => $this->method->label(),
            'amount_cents'         => $this->amount_cents,
            'discount_cents'       => $this->discount_cents ?? 0,
            'interest_cents'       => $this->interest_cents ?? 0,
            'fine_cents'           => $this->fine_cents ?? 0,
            'installment_number'   => $this->installment_number ?? 1,
            'total_installments'   => $this->total_installments ?? 1,
            'due_date'             => $this->due_date?->toDateString(),
            'status'               => $this->status->value,
            'status_label'         => $this->status->label(),
            'external_reference'   => $this->external_reference,
            'notes'                => $this->notes,
            'paid_at'              => $this->paid_at?->toISOString(),
            'created_at'           => $this->created_at?->toISOString(),
        ];
    }
}
