<?php

declare(strict_types=1);

namespace App\Modules\Settings\Services;

use App\Core\Tenancy\Models\TenantSettings;
use App\Core\Auth\Models\TenantUser;
use App\Modules\Catalog\Models\PriceList;
use Illuminate\Support\Facades\Cache;

/**
 * Calcula o limite máximo de desconto aplicável a um usuário.
 *
 * Hierarquia (mais restritivo vence):
 *   1. roles.policy.max_discount_percent — limite específico da role
 *   2. commercial.default_discount_limit — limite global do tenant
 *   3. price_lists.max_discount_percent  — limite da lista de preços usada
 */
final class DiscountPolicyService
{
    private const CACHE_TTL = 300;

    /**
     * Retorna o percentual máximo de desconto permitido.
     *
     * @return float 0–100
     */
    public function maxDiscountPercent(
        string  $tenantId,
        string  $userId,
        ?string $priceListId = null,
    ): float {
        $global    = $this->globalLimit($tenantId);
        $role      = $this->roleLimit($userId, $tenantId);
        $listLimit = $this->priceListLimit($tenantId, $priceListId);

        // Mais restritivo vence em todos os três níveis
        $limits = array_filter([$global, $role, $listLimit], fn ($v) => $v !== null);

        return count($limits) > 0 ? min($limits) : $global;
    }

    /**
     * Verifica se o percentual de desconto solicitado é permitido.
     */
    public function isAllowed(
        float   $requestedPercent,
        string  $tenantId,
        string  $userId,
        ?string $priceListId = null,
    ): bool {
        return $requestedPercent <= $this->maxDiscountPercent($tenantId, $userId, $priceListId);
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

    private function priceListLimit(string $tenantId, ?string $priceListId): ?float
    {
        if ($priceListId === null) {
            return null;
        }

        $value = Cache::remember(
            "pricelist_discount:{$tenantId}:{$priceListId}",
            self::CACHE_TTL,
            fn () => PriceList::where('tenant_id', $tenantId)
                ->where('uuid', $priceListId)
                ->value('max_discount_percent'),
        );

        if ($value === null) {
            return null;
        }

        $floatValue = (float) $value;

        // 0 significa "sem limite definido pela lista" — não aplicar restrição
        return $floatValue > 0 ? $floatValue : null;
    }
}
