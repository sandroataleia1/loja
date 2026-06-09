<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CustomerTagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'  => $this->uuid,
            'name'  => $this->name,
            'color' => $this->color,
        ];
    }
}
