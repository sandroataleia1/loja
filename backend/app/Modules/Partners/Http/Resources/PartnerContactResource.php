<?php

declare(strict_types=1);

namespace App\Modules\Partners\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PartnerContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'       => $this->uuid,
            'type'       => $this->type,
            'value'      => $this->value,
            'label'      => $this->label,
            'is_primary' => $this->is_primary,
        ];
    }
}
