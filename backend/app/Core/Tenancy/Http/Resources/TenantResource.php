<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'       => $this->uuid,
            'code'       => $this->code,
            'trade_name' => $this->trade_name,
            'legal_name' => $this->legal_name,
            'document'   => $this->document,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'is_active'  => $this->is_active,
        ];
    }
}
