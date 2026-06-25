<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Services\ViaCepService;
use Illuminate\Http\JsonResponse;

final class CepLookupController extends Controller
{
    public function __invoke(string $zipcode, ViaCepService $viaCep): JsonResponse
    {
        $address = $viaCep->lookup($zipcode);

        return response()->json([
            'success' => true,
            'data'    => $address,
        ]);
    }
}
