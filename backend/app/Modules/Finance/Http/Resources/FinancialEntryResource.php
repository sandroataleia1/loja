<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FinancialEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                  => $this->uuid,
            'store_id'              => $this->store_id,
            'type'                  => $this->type->value,
            'type_label'            => $this->type->label(),
            'status'                => $this->status->value,
            'status_label'          => $this->status->label(),
            'amount_cents'          => $this->amount_cents,
            'due_date'              => $this->due_date?->toDateString(),
            'paid_at'               => $this->paid_at?->toISOString(),
            'description'           => $this->description,
            'reference_type'        => $this->reference_type,
            'reference_id'          => $this->reference_id,
            'external_reference'    => $this->external_reference,
            'reconciliation_status' => $this->reconciliation_status?->value,
            'financial_account_id'  => $this->financial_account_id,
            'category_id'           => $this->category_id,
            'account'               => $this->whenLoaded('account', fn () => new FinancialAccountResource($this->account)),
            'category'              => $this->whenLoaded('category', fn () => new FinancialCategoryResource($this->category)),
            'installments'          => $this->whenLoaded('installments',
                fn () => FinancialInstallmentResource::collection($this->installments)),
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),
        ];
    }
}
