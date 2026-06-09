<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Resources;

use App\Modules\Inventory\Http\Resources\StoreResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CashRegisterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'code'         => $this->code,
            'name'         => $this->name,
            'is_active'    => $this->is_active,
            'store_id'     => $this->store_id,
            'store'        => $this->whenLoaded('store', fn () => new StoreResource($this->store)),
            'open_session' => $this->whenLoaded('openSession',
                fn () => $this->openSession ? new CashRegisterSessionResource($this->openSession) : null),
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
        ];
    }
}
