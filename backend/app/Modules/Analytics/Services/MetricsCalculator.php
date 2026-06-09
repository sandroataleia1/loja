<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Services;

use Carbon\Carbon;

/**
 * Cálculos estatísticos puros — sem I/O, sem dependências externas.
 * Usado por MetricsConsolidator e listeners de atualização incremental.
 */
final class MetricsCalculator
{
    public function averageTicket(int $totalOrders, float $totalSpent): float
    {
        if ($totalOrders === 0) {
            return 0.0;
        }

        return round($totalSpent / $totalOrders, 2);
    }

    /** @param int $unitsSold Total de unidades vendidas (não devolvidas) */
    public function returnRate(int $unitsSold, int $unitsReturned): float
    {
        if ($unitsSold === 0) {
            return 0.0;
        }

        return round(min($unitsReturned / $unitsSold, 1.0), 4);
    }

    /**
     * Frequência de compra em dias (média de intervalo entre compras).
     * Retorna null se menos de 2 compras (frequência indefinida).
     *
     * @param Carbon[] $purchaseDates Datas de compra em ordem qualquer
     */
    public function purchaseFrequency(array $purchaseDates): ?float
    {
        if (count($purchaseDates) < 2) {
            return null;
        }

        usort($purchaseDates, fn (Carbon $a, Carbon $b) => $a->timestamp <=> $b->timestamp);

        $gaps = [];
        for ($i = 1, $count = count($purchaseDates); $i < $count; $i++) {
            $gaps[] = abs($purchaseDates[$i]->diffInDays($purchaseDates[$i - 1]));
        }

        return round(array_sum($gaps) / count($gaps), 2);
    }

    public function daysSinceLastPurchase(?\DateTimeInterface $lastPurchaseAt): ?int
    {
        if ($lastPurchaseAt === null) {
            return null;
        }

        return (int) Carbon::instance($lastPurchaseAt)->diffInDays(now());
    }

    /** Giro de estoque: unidades vendidas / estoque médio. */
    public function stockTurnover(int $unitsSold, float $averageStock): float
    {
        if ($averageStock <= 0) {
            return 0.0;
        }

        return round($unitsSold / $averageStock, 4);
    }

    /** Converte centavos (integer) para reais (float). */
    public function centsToDecimal(int $cents): float
    {
        return round($cents / 100, 2);
    }
}
