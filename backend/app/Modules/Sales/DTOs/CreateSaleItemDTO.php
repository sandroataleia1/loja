<?php

declare(strict_types=1);

namespace App\Modules\Sales\DTOs;

final readonly class CreateSaleItemDTO
{
    public function __construct(
        public ?string $variantId,
        public string  $skuSnapshot,
        public string  $nameSnapshot,
        public array   $attributesSnapshot,
        public float   $quantity,
        public int     $unitPriceCents,
        public ?int    $costPriceCents,
        public int     $discountAmountCents,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            variantId:          $data['variant_id'] ?? null,
            skuSnapshot:        $data['sku_snapshot'],
            nameSnapshot:       $data['name_snapshot'],
            attributesSnapshot: $data['attributes_snapshot'] ?? [],
            quantity:           (float) $data['quantity'],
            unitPriceCents:     (int) $data['unit_price_cents'],
            costPriceCents:     isset($data['cost_price_cents']) ? (int) $data['cost_price_cents'] : null,
            discountAmountCents: (int) ($data['discount_amount_cents'] ?? 0),
        );
    }

    public function subtotalCents(): int
    {
        return (int) round($this->quantity * $this->unitPriceCents);
    }

    public function totalCents(): int
    {
        return $this->subtotalCents() - $this->discountAmountCents;
    }
}
