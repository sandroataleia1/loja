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
            'uuid'               => $this->uuid,
            'method'             => $this->method->value,
            'method_label'       => $this->method->label(),
            'amount_cents'       => $this->amount_cents,
            'status'             => $this->status->value,
            'status_label'       => $this->status->label(),
            'external_reference' => $this->external_reference,
            'notes'              => $this->notes,
            'paid_at'            => $this->paid_at?->toISOString(),
            'created_at'         => $this->created_at?->toISOString(),
        ];
    }
}
