<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FinancialInstallmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'               => $this->uuid,
            'installment_number' => $this->installment_number,
            'due_date'           => $this->due_date?->toDateString(),
            'amount_cents'       => $this->amount_cents,
            'status'             => $this->status->value,
            'status_label'       => $this->status->label(),
            'paid_at'            => $this->paid_at?->toISOString(),
        ];
    }
}
