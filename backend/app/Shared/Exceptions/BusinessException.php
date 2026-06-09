<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use Illuminate\Http\Response;

final class BusinessException extends AppException
{
    public function httpStatus(): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }
}
