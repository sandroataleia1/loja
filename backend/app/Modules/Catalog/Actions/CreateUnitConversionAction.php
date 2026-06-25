<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\CreateUnitConversionDTO;
use App\Modules\Catalog\Models\UnitConversion;
use App\Shared\Exceptions\ConflictException;

final readonly class CreateUnitConversionAction
{
    public function execute(CreateUnitConversionDTO $dto): UnitConversion
    {
        $exists = UnitConversion::query()
            ->where('from_unit', $dto->fromUnit)
            ->where('to_unit', $dto->toUnit)
            ->where('product_id', $dto->productId)
            ->where('variant_id', $dto->variantId)
            ->exists();

        if ($exists) {
            throw new ConflictException(
                "Já existe uma conversão de {$dto->fromUnit} para {$dto->toUnit} neste escopo."
            );
        }

        return UnitConversion::create([
            'from_unit'  => $dto->fromUnit,
            'to_unit'    => $dto->toUnit,
            'factor'     => $dto->factor,
            'product_id' => $dto->productId,
            'variant_id' => $dto->variantId,
            'notes'      => $dto->notes,
            'is_active'  => $dto->isActive,
        ]);
    }
}
