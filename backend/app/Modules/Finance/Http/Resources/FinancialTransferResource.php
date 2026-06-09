<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FinancialTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                   => $this->uuid,
            'origin_account_id'      => $this->origin_account_id,
            'destination_account_id' => $this->destination_account_id,
            'amount_cents'           => $this->amount_cents,
            'transferred_at'         => $this->transferred_at?->toISOString(),
            'description'            => $this->description,
            'origin_account'         => $this->whenLoaded('originAccount',
                fn () => new FinancialAccountResource($this->originAccount)),
            'destination_account'    => $this->whenLoaded('destinationAccount',
                fn () => new FinancialAccountResource($this->destinationAccount)),
            'created_at'             => $this->created_at?->toISOString(),
        ];
    }
}
