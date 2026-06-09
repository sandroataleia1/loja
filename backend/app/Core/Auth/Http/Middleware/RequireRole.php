<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Middleware;

use App\Core\Auth\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica se o usuário autenticado possui um dos roles exigidos no tenant atual.
 *
 * Uso nas rotas: middleware('role:owner,manager')
 * Múltiplos slugs separados por vírgula são tratados como OR.
 */
final class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
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

        // Normalize: middleware('role:owner,manager') passes as one arg with comma
        $allowedSlugs = [];
        foreach ($roles as $roleArg) {
            foreach (explode(',', $roleArg) as $slug) {
                $allowedSlugs[] = trim($slug);
            }
        }

        $membership = $user->tenantMembership();

        if ($membership === null) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado.',
                'errors'  => [],
            ], Response::HTTP_FORBIDDEN);
        }

        $roleSlug = $membership->role?->slug;

        if ($roleSlug === null || ! in_array($roleSlug, $allowedSlugs, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado.',
                'errors'  => [],
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
