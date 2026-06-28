<?php

declare(strict_types=1);

use App\Core\Tenancy\Services\FeatureManager;
use App\Core\Tenancy\Services\TenantContext;

if (! function_exists('feature')) {
    /**
     * Verifica se uma feature flag está habilitada para o tenant atual.
     * Retorna false quando não há tenant no contexto (ex: console, testes sem tenant).
     */
    function feature(string $key): bool
    {
        try {
            $tenantId = TenantContext::getId();

            if (! $tenantId) {
                return false;
            }

            return app(FeatureManager::class)->isEnabledByKey($tenantId, $key);
        } catch (Throwable) {
            return false;
        }
    }
}
