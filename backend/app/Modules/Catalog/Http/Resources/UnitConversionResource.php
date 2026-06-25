<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UnitConversionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'       => $this->uuid,
            'from_unit'  => $this->from_unit instanceof \BackedEnum ? $this->from_unit->value : $this->from_unit,
            'to_unit'    => $this->to_unit instanceof \BackedEnum ? $this->to_unit->value : $this->to_unit,
            'factor'     => $this->factor,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'notes'      => $this->notes,
            'is_active'  => $this->is_active,
            'is_global'  => $this->isGlobal(),
            'label'      => sprintf(
                '1 %s = %s %s',
                $this->from_unit instanceof \BackedEnum ? $this->from_unit->value : $this->from_unit,
                number_format($this->factor, 3, ',', '.'),
                $this->to_unit instanceof \BackedEnum ? $this->to_unit->value : $this->to_unit,
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
