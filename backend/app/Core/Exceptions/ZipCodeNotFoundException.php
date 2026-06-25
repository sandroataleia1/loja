<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class ZipCodeNotFoundException extends HttpResponseException
{
    public function __construct(string $zipcode)
    {
        parent::__construct(new JsonResponse([
            'success' => false,
            'message' => "CEP {$zipcode} não encontrado.",
        ], 422));
    }
}
