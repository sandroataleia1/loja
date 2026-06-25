<?php

declare(strict_types=1);

namespace App\Modules\Carriers\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CarrierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'       => $this->uuid,
            'code'       => $this->code,
            'name'       => $this->name,
            'trade_name' => $this->trade_name,
            'cnpj'       => $this->cnpj,
            'ie'         => $this->ie,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'notes'      => $this->notes,
            'is_active'     => $this->is_active,
            'delivery_mode' => $this->delivery_mode?->value ?? $this->delivery_mode,
            'rntrc'         => $this->rntrc,
            'addresses'     => CarrierAddressResource::collection($this->whenLoaded('addresses')),
            'contacts'   => CarrierContactResource::collection($this->whenLoaded('contacts')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
