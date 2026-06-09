<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FinancialAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                  => $this->uuid,
            'code'                  => $this->code,
            'name'                  => $this->name,
            'type'                  => $this->type->value,
            'type_label'            => $this->type->label(),
            'is_active'             => $this->is_active,
            'current_balance_cents' => $this->current_balance_cents,
            'store_id'              => $this->store_id,
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),
        ];
    }
}
