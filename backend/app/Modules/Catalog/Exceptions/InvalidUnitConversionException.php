<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Exceptions;

use App\Shared\Exceptions\AppException;
use Illuminate\Http\Response;

final class InvalidUnitConversionException extends AppException
{
    public function httpStatus(): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }

    public static function incompatibleGroups(string $fromUnit, string $toUnit): self
    {
        return new self(
            message: "Não é possível converter entre grupos de unidades distintos: '{$fromUnit}' e '{$toUnit}'.",
        );
    }
}
