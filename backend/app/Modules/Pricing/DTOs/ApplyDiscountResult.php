<?php

declare(strict_types=1);

namespace App\Modules\Pricing\DTOs;

final readonly class ApplyDiscountResult
{
    public function __construct(
        public int   $original_price_cents,
        public float $discount_percent,
        public int   $discount_cents,
        public int   $final_price_cents,
        public float $max_allowed_percent,
    ) {}

    public function toArray(): array
    {
        return [
            'original_price_cents' => $this->original_price_cents,
            'discount_percent'     => $this->discount_percent,
            'discount_cents'       => $this->discount_cents,
            'final_price_cents'    => $this->final_price_cents,
            'max_allowed_percent'  => $this->max_allowed_percent,
        ];
    }
}
