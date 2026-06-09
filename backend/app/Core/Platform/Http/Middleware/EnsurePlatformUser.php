<?php

declare(strict_types=1);

namespace App\Core\Platform\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePlatformUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user('platform')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
