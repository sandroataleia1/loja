<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class ZipCodeServiceUnavailableException extends HttpResponseException
{
    public function __construct()
    {
        parent::__construct(new JsonResponse([
            'success' => false,
            'message' => 'Serviço de consulta de CEP temporariamente indisponível. Preencha o endereço manualmente.',
        ], 503));
    }
}
