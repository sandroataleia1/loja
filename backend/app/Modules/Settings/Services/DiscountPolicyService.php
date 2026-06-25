<?php

declare(strict_types=1);

namespace App\Modules\Settings\Services;

use App\Core\Tenancy\Models\TenantSettings;
use App\Core\Auth\Models\TenantUser;

/**
 * Calcula o limite máximo de desconto aplicável a um usuário.
 *
 * Hierarquia (mais restritivo vence):
 *   1. roles.policy.max_discount_percent — limite específico da role
 *   2. commercial.default_discount_limit — limite global do tenant
 *
 * Se a role não tem policy definida, usa apenas o global.
 */
final class DiscountPolicyService
{
    /**
     * Retorna o percentual máximo de desconto permitido para o usuário no tenant.
     *
     * @return float 0–100
     */
    public function maxDiscountPercent(string $userId, string $tenantId): float
    {
        $global = $this->globalLimit($tenantId);
        $role   = $this->roleLimit($userId, $tenantId);

        if ($role === null) {
            return $global;
        }

        // Mais restritivo vence
        return min($role, $global);
    }

    /**
     * Verifica se o percentual de desconto solicitado é permitido.
     */
    public function isAllowed(float $requestedPercent, string $userId, string $tenantId): bool
    {
        return $requestedPercent <= $this->maxDiscountPercent($userId, $tenantId);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function globalLimit(string $tenantId): float
    {
        $settings = TenantSettings::forTenant($tenantId);
        $section  = $settings->getSection('commercial');

        return (float) ($section['default_discount_limit'] ?? 10.0);
    }

    private function roleLimit(string $userId, string $tenantId): ?float
    {
        $tenantUser = TenantUser::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->with('role')
            ->first();

        if ($tenantUser === null || $tenantUser->role === null) {
            return null;
        }

        $policy = $tenantUser->role->policy;

        if (! is_array($policy) || ! isset($policy['max_discount_percent'])) {
            return null;
        }

        return (float) $policy['max_discount_percent'];
    }
}
