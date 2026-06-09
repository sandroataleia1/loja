<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CashMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'type'         => $this->type->value,
            'type_label'   => $this->type->label(),
            'amount_cents' => $this->amount_cents,
            'description'  => $this->description,
            'created_by'   => $this->created_by,
            'created_at'   => $this->created_at?->toISOString(),
        ];
    }
}
