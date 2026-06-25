<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CostCenterResource extends JsonResource
{
    private static array $TYPE_LABELS = [
        'ADMINISTRATIVE' => 'Administrativo',
        'COMMERCIAL'     => 'Comercial',
        'LOGISTICS'      => 'Logística',
        'PURCHASING'     => 'Compras',
        'FINANCIAL'      => 'Financeiro',
    ];

    public function toArray(Request $request): array
    {
        return [
            'uuid'       => $this->uuid,
            'parent_id'  => $this->parent_id,
            'code'       => $this->code,
            'name'       => $this->name,
            'type'       => $this->type,
            'type_label' => self::$TYPE_LABELS[$this->type] ?? $this->type,
            'is_active'  => $this->is_active,
            'children'   => CostCenterResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
