<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Middleware;

use App\Core\Auth\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica se o usuário autenticado possui a permissão exigida no contexto do tenant atual.
 *
 * Uso nas rotas: middleware('permission:products.view')
 */
final class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $user->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado.',
                'errors'  => [],
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
