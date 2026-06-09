<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Middleware;

use App\Core\Auth\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica se o usuário autenticado tem acesso à loja identificada pelo
 * parâmetro de rota `store_id` ou, como fallback, pelo campo `store_id` do corpo da requisição.
 *
 * Uso nas rotas: middleware('store_access')
 * Ausência de allowlist = acesso irrestrito (retorna 200).
 */
final class RequireStoreAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado.',
                'errors'  => [],
            ], Response::HTTP_FORBIDDEN);
        }

        // Resolve store_id: route parameter takes precedence over request body
        $storeId = $request->route('store_id')
            ?? $request->route('storeId')
            ?? $request->input('store_id');

        if ($storeId === null) {
            // No store_id to check — allow through
            return $next($request);
        }

        if (! $user->canAccessStore((string) $storeId)) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado.',
                'errors'  => [],
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
