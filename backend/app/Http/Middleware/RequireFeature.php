<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Features\FeatureEnum;
use App\Core\Tenancy\Services\FeatureManager;
use App\Core\Tenancy\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueia acesso a rotas que requerem uma feature flag ativa.
 *
 * Uso: ->middleware('feature:customer.guarantor')
 */
final class RequireFeature
{
    public function __construct(private readonly FeatureManager $features) {}

    public function handle(Request $request, Closure $next, string $featureSlug): Response
    {
        $tenantId = TenantContext::getId();

        if ($tenantId === null) {
            abort(403, 'Contexto de tenant não encontrado.');
        }

        $feature = FeatureEnum::tryFrom($featureSlug);

        if ($feature === null) {
            abort(403, "Feature desconhecida: {$featureSlug}");
        }

        if (! $this->features->isEnabled($tenantId, $feature)) {
            abort(403, "O módulo \"{$feature->label()}\" não está habilitado para este tenant.");
        }

        return $next($request);
    }
}
