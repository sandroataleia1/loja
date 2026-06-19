<?php

declare(strict_types=1);

namespace App\Modules\Pix\Services;

use App\Core\Tenancy\Scopes\TenantScope;
use App\Modules\Pix\Contracts\PixGatewayContract;
use App\Modules\Pix\Gateways\AsaasPixGateway;
use App\Modules\Pix\Gateways\MockPixGateway;
use App\Modules\Pix\Models\TenantPaymentGateway;
use App\Shared\Exceptions\BusinessException;

final class PixGatewayResolver
{
    /**
     * Resolve the active PIX gateway for a tenant by UUID.
     * Bypasses the BelongsToTenant global scope so it can be called from
     * the webhook controller (which has no authenticated tenant context).
     */
    public function resolveByTenantUuid(string $tenantUuid): PixGatewayContract
    {
        $config = TenantPaymentGateway::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantUuid)
            ->where('is_active', true)
            ->first();

        return $this->build($config);
    }

    /**
     * Resolve the active PIX gateway for the currently authenticated tenant.
     * Relies on the BelongsToTenant global scope to filter by tenant.
     */
    public function resolveForCurrentTenant(): PixGatewayContract
    {
        $config = TenantPaymentGateway::where('is_active', true)->first();

        return $this->build($config);
    }

    /**
     * Get gateway config for current tenant (including inactive).
     */
    public function configForCurrentTenant(): ?TenantPaymentGateway
    {
        return TenantPaymentGateway::first();
    }

    private function build(?TenantPaymentGateway $config): PixGatewayContract
    {
        if (! $config) {
            throw new BusinessException('Gateway PIX não configurado para este tenant.');
        }

        return match ($config->gateway) {
            'asaas' => new AsaasPixGateway($config),
            'mock'  => new MockPixGateway(),
            default => throw new BusinessException("Gateway '{$config->gateway}' não suportado."),
        };
    }
}
