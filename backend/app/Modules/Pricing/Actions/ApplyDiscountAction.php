<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Actions;

use App\Modules\Pricing\DTOs\ApplyDiscountResult;
use App\Modules\Pricing\Exceptions\DiscountExceedsLimitException;
use App\Modules\Settings\Services\DiscountPolicyService;

final readonly class ApplyDiscountAction
{
    public function __construct(private DiscountPolicyService $discountPolicy) {}

    /**
     * Aplica um desconto a um preço, validando contra os limites do usuário e da lista.
     *
     * @throws DiscountExceedsLimitException quando o desconto excede o limite permitido
     */
    public function execute(
        float   $discountPercent,
        int     $originalPriceCents,
        string  $tenantId,
        string  $userId,
        ?string $priceListId = null,
    ): ApplyDiscountResult {
        $maxAllowed = $this->discountPolicy->maxDiscountPercent($tenantId, $userId, $priceListId);

        if ($discountPercent > $maxAllowed) {
            throw new DiscountExceedsLimitException($discountPercent, $maxAllowed);
        }

        $discountCents = (int) round($originalPriceCents * ($discountPercent / 100));

        return new ApplyDiscountResult(
            original_price_cents: $originalPriceCents,
            discount_percent:     $discountPercent,
            discount_cents:       $discountCents,
            final_price_cents:    $originalPriceCents - $discountCents,
            max_allowed_percent:  $maxAllowed,
        );
    }
}
