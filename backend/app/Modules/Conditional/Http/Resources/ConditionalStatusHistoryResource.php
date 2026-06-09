<?php

declare(strict_types=1);

namespace App\Modules\Conditional\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConditionalStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'            => $this->uuid,
            'previous_status' => $this->previous_status,
            'current_status'  => $this->current_status,
            'changed_by'      => $this->changed_by,
            'changed_at'      => $this->changed_at?->toISOString(),
        ];
    }
}
