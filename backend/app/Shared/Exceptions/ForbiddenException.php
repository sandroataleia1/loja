<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use Illuminate\Http\Response;

final class ForbiddenException extends AppException
{
    public function httpStatus(): int
    {
        return Response::HTTP_FORBIDDEN;
    }
}
