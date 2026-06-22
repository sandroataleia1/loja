<?php

declare(strict_types=1);

namespace App\Modules\Orders\Services;

use App\Core\Auth\Models\TenantUser;
use App\Core\Tenancy\Services\TenantContext;
use Illuminate\Validation\ValidationException;

/**
 * Valida se o desconto aplicado respeita o limite configurado no perfil do vendedor.
 *
 * Cada Role pode ter um `policy.discount_limit_percentage` (float|null).
 * null   → sem limite (o perfil pode conceder qualquer desconto)
 * float  → percentual máximo permitido (ex: 10.0 = 10%)
 *
 * Caso o desconto ultrapasse o limite, lança ValidationException com mensagem
 * orientando o usuário a solicitar aprovação de um gestor.
 */
final class DiscountApprovalService
{
    /**
     * @param string|null $sellerUuid   UUID do vendedor identificado pelo PIN (null = usuário autenticado)
     * @param string      $discountType 'fixed' (valor em reais) | 'percentage' (percentual direto)
     * @param float       $discountValue valor do desconto (reais ou %, conforme discountType)
     * @param int         $subtotalCents total bruto dos itens em centavos (para converter fixed→%)
     */
    public function enforce(
        ?string $sellerUuid,
        string  $discountType,
        float   $discountValue,
        int     $subtotalCents,
    ): void {
        if ($discountValue <= 0) {
            return;
        }

        $discountPct = $this->toPercentage($discountType, $discountValue, $subtotalCents);

        if ($discountPct <= 0) {
            return;
        }

        $limit = $this->resolveLimit($sellerUuid);

        if ($limit === null) {
            return; // perfil sem limite configurado — qualquer desconto é permitido
        }

        if ($discountPct > $limit) {
            throw ValidationException::withMessages([
                'discount_value' => [sprintf(
                    'Desconto de %.2f%% excede o limite de %.2f%% permitido para este perfil. Solicite aprovação de um gestor.',
                    $discountPct,
                    $limit,
                )],
            ]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function toPercentage(string $type, float $value, int $subtotalCents): float
    {
        if ($type === 'percentage') {
            return $value;
        }

        // 'fixed': converte valor em reais para percentual do subtotal em centavos
        if ($subtotalCents <= 0) {
            return 0.0;
        }

        return (($value * 100) / $subtotalCents) * 100;
    }

    private function resolveLimit(?string $sellerUuid): ?float
    {
        $tenantId = TenantContext::getIdOrFail();
        $userId   = $sellerUuid ?? auth()->id();

        if ($userId === null) {
            return null;
        }

        $tenantUser = TenantUser::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->with('role')
            ->first();

        return $tenantUser?->role?->discountLimitPercentage();
    }
}
