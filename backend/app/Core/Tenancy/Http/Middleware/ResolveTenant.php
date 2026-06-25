<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Http\Middleware;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve o tenant ativo para a request em três estratégias, em cascata:
 *
 *  1. Header X-Tenant-ID (UUID) — APIs e clientes multi-tenant.
 *  2. Subdomínio (slug do host, ex: empresa.app.com.br → slug "empresa").
 *  3. Fallback: tenant_id direto do usuário autenticado.
 *
 * Para as estratégias 1 e 2, valida que o usuário autenticado é membro
 * do tenant resolvido (tenant_id no user OU registro em tenant_users).
 */
final class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if ($tenant === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not identified.',
                'errors'  => [],
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Estratégias 1 e 2 exigem verificação de membership
        if ($this->resolvedViaHeaderOrSubdomain($request, $tenant)) {
            if (! $this->userIsMember($request, $tenant)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado a este tenant.',
                    'errors'  => [],
                ], Response::HTTP_FORBIDDEN);
            }
        }

        TenantContext::set($tenant->uuid);

        return $next($request);
    }

    // ── Resolução do tenant ───────────────────────────────────────────────────

    private function resolveTenant(Request $request): ?Tenant
    {
        return $this->resolveByHeader($request)
            ?? $this->resolveBySubdomain($request)
            ?? $this->resolveByUser($request);
    }

    /** Estratégia 1: Header X-Tenant-ID contém o UUID do tenant. */
    private function resolveByHeader(Request $request): ?Tenant
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (blank($tenantId)) {
            return null;
        }

        return Tenant::where('uuid', $tenantId)->where('is_active', true)->first();
    }

    /**
     * Estratégia 2: subdomínio do host.
     * Extrai o primeiro segmento (ex: empresa.app.com.br → "empresa")
     * e busca pelo slug do tenant. Ignora "www" e "api".
     */
    private function resolveBySubdomain(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $parts = explode('.', $host);

        if (count($parts) < 3) {
            return null;
        }

        $subdomain = $parts[0];

        if (in_array($subdomain, ['www', 'api', 'app'], true)) {
            return null;
        }

        return Tenant::where('slug', $subdomain)->where('is_active', true)->first();
    }

    /** Estratégia 3 (fallback): tenant_id direto no usuário autenticado. */
    private function resolveByUser(Request $request): ?Tenant
    {
        $tenantId = $request->user()?->tenant_id;

        if (blank($tenantId)) {
            return null;
        }

        return Tenant::where('uuid', $tenantId)->where('is_active', true)->first();
    }

    // ── Validação de membership ───────────────────────────────────────────────

    /**
     * Verifica se o tenant foi resolvido por header ou subdomínio
     * (nesse caso requer verificação de membership explícita).
     */
    private function resolvedViaHeaderOrSubdomain(Request $request, Tenant $tenant): bool
    {
        // Se o tenant bate com o tenant_id do usuário, foi fallback — sem verificação extra
        $userTenantId = $request->user()?->tenant_id;

        return $tenant->uuid !== $userTenantId;
    }

    /**
     * O usuário é membro do tenant se:
     *   - tenant_id direto no user coincide, OU
     *   - existe registro ativo em tenant_users
     */
    private function userIsMember(Request $request, Tenant $tenant): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        if ($user->tenant_id === $tenant->uuid) {
            return true;
        }

        return $user->memberships()
            ->where('tenant_id', $tenant->uuid)
            ->where('is_active', true)
            ->exists();
    }
}
