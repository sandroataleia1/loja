<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'        => $this->uuid,
            'code'        => $this->code,
            'person_type' => $this->person_type,
            'name'        => $this->name,
            'trade_name'  => $this->trade_name,
            'document'    => $this->document,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'is_active'   => $this->is_active,
            'notes'       => $this->notes,
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
