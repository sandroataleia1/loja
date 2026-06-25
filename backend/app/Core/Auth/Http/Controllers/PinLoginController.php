<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Actions\PinLoginAction;
use App\Core\Auth\Http\Requests\PinLoginRequest;
use App\Core\Auth\Http\Resources\UserResource;
use App\Http\Controllers\Controller;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Login rápido por PIN numérico — utilizado principalmente no PDV.
 *
 * Não requer autenticação prévia (endpoint público).
 * Throttle: throttle:pin-login (10 req / 5 min por tenant_id + IP).
 */
final class PinLoginController extends Controller
{
    use HasApiResponse;

    public function __invoke(PinLoginRequest $request, PinLoginAction $action): JsonResponse
    {
        $result = $action->execute(
            tenantId:   $request->validated('tenant_id'),
            pin:        $request->validated('pin'),
            deviceName: $request->validated('device_name'),
        );

        return $this->success([
            'token' => $result['token'],
            'user'  => new UserResource($result['user']),
        ]);
    }
}
