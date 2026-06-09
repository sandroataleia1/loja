<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Http\Middleware;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->resolveTenantId($request);

        if ($tenantId === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not identified.',
                'errors'  => [],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $tenant = Tenant::where('uuid', $tenantId)->where('is_active', true)->first();

        if ($tenant === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found or inactive.',
                'errors'  => [],
            ], Response::HTTP_UNAUTHORIZED);
        }

        TenantContext::set($tenant->uuid);

        return $next($request);
    }

    private function resolveTenantId(Request $request): ?string
    {
        return $request->user()?->tenant_id;
    }
}
