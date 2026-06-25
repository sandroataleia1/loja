<?php

declare(strict_types=1);

namespace App\Core\Auth\Services;

use App\Core\Auth\Models\ApprovalRule;
use Illuminate\Support\Facades\Cache;

/**
 * Consulta regras de aprovação configuradas pelo tenant.
 *
 * Regras são cacheadas no Redis por 5 minutos (invalidar ao criar/alterar regra).
 * Se não existe regra ativa para a operação → não requer aprovação (permissivo).
 */
final class ApprovalRuleService
{
    private const TTL_SECONDS = 300;

    public function shouldRequireApproval(
        string $operationType,
        float  $value,
        string $tenantId,
    ): bool {
        $rule = $this->getRule($operationType, $tenantId);

        if ($rule === null) {
            return false;
        }

        return $rule->requiresApproval($value);
    }

    public function getRule(string $operationType, string $tenantId): ?ApprovalRule
    {
        $cacheKey = "approval_rule:{$tenantId}:{$operationType}";

        return Cache::remember(
            $cacheKey,
            self::TTL_SECONDS,
            fn () => ApprovalRule::where('tenant_id', $tenantId)
                ->where('operation_type', $operationType)
                ->where('is_active', true)
                ->first(),
        );
    }

    public function invalidate(string $operationType, string $tenantId): void
    {
        Cache::forget("approval_rule:{$tenantId}:{$operationType}");
    }

    public function invalidateAll(string $tenantId): void
    {
        // Invalida para todos os tipos conhecidos de aprovação
        $types = ['discount', 'cancellation', 'reversal', 'large_refund'];
        foreach ($types as $type) {
            $this->invalidate($type, $tenantId);
        }
    }
}
