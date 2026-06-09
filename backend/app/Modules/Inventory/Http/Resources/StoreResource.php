<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'name'         => $this->name,
            'code'         => $this->code,
            'type'         => $this->type,
            'phone'        => $this->phone,
            'email'        => $this->email,
            'address'      => $this->address,
            'is_active'    => $this->is_active,
            'is_ecommerce' => $this->is_ecommerce,
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
        ];
    }
}
