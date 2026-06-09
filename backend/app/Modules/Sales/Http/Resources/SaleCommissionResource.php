<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SaleCommissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'user_id'      => $this->user_id,
            'percentage'   => $this->percentage,
            'amount_cents' => $this->amount_cents,
            'created_at'   => $this->created_at?->toISOString(),
        ];
    }
}
