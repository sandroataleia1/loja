<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TaxProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'        => $this->uuid,
            'name'        => $this->name,
            'regime'      => $this->regime->value,
            'regime_label' => $this->regime->label(),
            'metadata'    => $this->metadata,
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
