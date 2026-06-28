<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Exceptions;

use RuntimeException;

final class DiscountExceedsLimitException extends RuntimeException
{
    public function __construct(
        public readonly float $requested,
        public readonly float $maxAllowed,
    ) {
        parent::__construct(
            sprintf(
                'Desconto de %.2f%% excede o limite máximo permitido de %.2f%%.',
                $requested,
                $maxAllowed,
            ),
        );
    }

    public function httpStatusCode(): int
    {
        return 422;
    }
}
