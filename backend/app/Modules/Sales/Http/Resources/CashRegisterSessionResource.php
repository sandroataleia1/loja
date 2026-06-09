<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Resources;

use App\Modules\Inventory\Http\Resources\StoreResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CashRegisterSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                    => $this->uuid,
            'code'                    => $this->code,
            'store_id'                => $this->store_id,
            'cash_register_id'        => $this->cash_register_id,
            'user_id'                 => $this->user_id,
            'closed_by'               => $this->closed_by,
            'status'                  => $this->status->value,
            'status_label'            => $this->status->label(),
            'opening_amount_cents'    => $this->opening_amount_cents,
            'closing_amount_cents'    => $this->closing_amount_cents,
            'expected_balance_cents'  => $this->expected_balance_cents,
            'difference_amount_cents' => $this->difference_amount_cents,
            'difference_reason'       => $this->difference_reason,
            'notes'                   => $this->notes,
            'opened_at'               => $this->opened_at?->toISOString(),
            'closed_at'               => $this->closed_at?->toISOString(),
            'store'                   => $this->whenLoaded('store',
                fn () => new StoreResource($this->store)),
            'cash_register'           => $this->whenLoaded('cashRegister',
                fn () => $this->cashRegister ? new CashRegisterResource($this->cashRegister) : null),
            'movements'               => $this->whenLoaded('movements',
                fn () => CashMovementResource::collection($this->movements)),
            'created_at'              => $this->created_at?->toISOString(),
            'updated_at'              => $this->updated_at?->toISOString(),
        ];
    }
}
