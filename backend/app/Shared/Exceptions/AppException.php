<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

abstract class AppException extends Exception
{
    public function __construct(
        string $message = '',
        private readonly array $errors = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    abstract public function httpStatus(): int;

    public function errors(): array
    {
        return $this->errors;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors'  => $this->errors,
        ], $this->httpStatus());
    }
}
