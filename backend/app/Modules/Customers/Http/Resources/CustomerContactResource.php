<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CustomerContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'        => $this->uuid,
            'customer_id' => $this->customer_id,
            'type'        => $this->type?->value,
            'type_label'  => $this->type?->label(),
            'value'       => $this->value,
            'label'       => $this->label,
            'is_primary'  => $this->is_primary,
        ];
    }
}
