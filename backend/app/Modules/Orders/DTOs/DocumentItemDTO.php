<?php

declare(strict_types=1);

namespace App\Modules\Orders\DTOs;

/** Representa um item de orçamento ou pedido. */
final readonly class DocumentItemDTO
{
    public function __construct(
        public ?string $productVariantId,
        public string  $nameSnapshot,
        public ?string $skuSnapshot,
        public string  $unitOfMeasure,
        public float   $quantity,
        public int     $unitPriceCents,
        public int     $discountCents,
        public int     $subtotalCents,
        public int     $sortOrder,
        public ?string $notes,
    ) {}

    public static function fromArray(array $data, int $index = 0): static
    {
        $qty      = (float) ($data['quantity'] ?? 1);
        $price    = (int) ($data['unit_price_cents'] ?? 0);
        $discount = (int) ($data['discount_cents'] ?? 0);
        $subtotal = (int) round($qty * $price) - $discount;

        return new static(
            productVariantId: $data['product_variant_id'] ?? null,
            nameSnapshot:     $data['name_snapshot'],
            skuSnapshot:      $data['sku_snapshot'] ?? null,
            unitOfMeasure:    $data['unit_of_measure'] ?? 'UN',
            quantity:         $qty,
            unitPriceCents:   $price,
            discountCents:    max(0, $discount),
            subtotalCents:    max(0, $subtotal),
            sortOrder:        (int) ($data['sort_order'] ?? $index),
            notes:            $data['notes'] ?? null,
        );
    }
}
