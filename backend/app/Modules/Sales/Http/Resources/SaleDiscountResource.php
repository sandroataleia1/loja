<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SaleDiscountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'sale_item_id' => $this->sale_item_id,
            'type'         => $this->type->value,
            'type_label'   => $this->type->label(),
            'percentage'   => $this->percentage,
            'amount_cents' => $this->amount_cents,
            'reason'       => $this->reason,
            'approved_by'  => $this->approved_by,
            'created_at'   => $this->created_at?->toISOString(),
        ];
    }
}
