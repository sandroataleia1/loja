<?php

declare(strict_types=1);

namespace App\Modules\Carriers\Services;

use App\Modules\Carriers\Models\CarrierFreightTable;

final readonly class FreightResult
{
    public function __construct(
        public readonly int    $priceCents,
        public readonly ?int   $estimatedDays,
        public readonly string $carrierId,
        public readonly string $freightTableId,
        public readonly string $pricingType,
    ) {}

    public function toArray(): array
    {
        return [
            'price_cents'      => $this->priceCents,
            'price_formatted'  => 'R$ ' . number_format($this->priceCents / 100, 2, ',', '.'),
            'estimated_days'   => $this->estimatedDays,
            'carrier_id'       => $this->carrierId,
            'freight_table_id' => $this->freightTableId,
            'pricing_type'     => $this->pricingType,
        ];
    }
}

final readonly class FreightCalculatorService
{
    public function calculate(
        string $carrierId,
        string $destCep,
        float  $weightG,
        int    $valueCents,
        ?string $freightTableId = null,
    ): ?FreightResult {
        $query = CarrierFreightTable::where('carrier_id', $carrierId)->where('is_active', true);

        if ($freightTableId) {
            $query->where('uuid', $freightTableId);
        }

        $freightTable = $query->with('ranges')->first();

        if (! $freightTable) {
            return null;
        }

        if ($freightTable->pricing_type === 'free') {
            return new FreightResult(0, null, $carrierId, $freightTable->uuid, 'free');
        }

        if ($freightTable->pricing_type === 'fixed') {
            $range = $freightTable->ranges->first();
            if ($range) {
                return new FreightResult($range->price_cents, $range->estimated_days, $carrierId, $freightTable->uuid, 'fixed');
            }
        }

        $cleanCep = preg_replace('/\D/', '', $destCep);

        foreach ($freightTable->ranges as $range) {
            $match = match ($freightTable->pricing_type) {
                'by_weight'    => $this->matchWeight($range, $weightG),
                'by_value'     => $this->matchValue($range, $valueCents),
                'by_cep_range' => $this->matchCep($range, $cleanCep),
                default        => false,
            };

            if ($match) {
                return new FreightResult(
                    $range->price_cents,
                    $range->estimated_days,
                    $carrierId,
                    $freightTable->uuid,
                    $freightTable->pricing_type,
                );
            }
        }

        return null;
    }

    private function matchWeight(object $range, float $weightG): bool
    {
        $min = $range->min_weight_g ?? 0;
        $max = $range->max_weight_g ?? PHP_INT_MAX;

        return $weightG >= $min && $weightG <= $max;
    }

    private function matchValue(object $range, int $valueCents): bool
    {
        $min = $range->min_value_cents ?? 0;
        $max = $range->max_value_cents ?? PHP_INT_MAX;

        return $valueCents >= $min && $valueCents <= $max;
    }

    private function matchCep(object $range, string $cleanCep): bool
    {
        if (! $range->min_cep || ! $range->max_cep) {
            return false;
        }

        $minCep = preg_replace('/\D/', '', $range->min_cep);
        $maxCep = preg_replace('/\D/', '', $range->max_cep);

        return $cleanCep >= $minCep && $cleanCep <= $maxCep;
    }
}
