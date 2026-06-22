<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\PaymentCondition;
use Carbon\Carbon;

final class InstallmentCalculatorService
{
    /**
     * Calcula o cronograma de parcelas para uma condição de pagamento.
     *
     * Retorna array com:
     *   original_amount_cents  - valor original sem ajustes
     *   discount_cents         - desconto aplicado
     *   interest_cents         - juros de parcelamento embutidos
     *   total_amount_cents     - valor final após desconto + juros
     *   installments           - array de parcelas com number, due_date, amount_cents
     *
     * Juros de atraso (fine_percent) NÃO são calculados aqui — aplicados no ato do recebimento.
     *
     * @return array{
     *   original_amount_cents: int,
     *   discount_cents: int,
     *   interest_cents: int,
     *   total_amount_cents: int,
     *   installments: array<array{number: int, due_date: string, amount_cents: int}>
     * }
     */
    public function calculate(
        PaymentCondition $condition,
        int $amountCents,
        Carbon $saleDate,
        ?int $customInstallmentCount = null,
    ): array {
        // 1. Desconto
        $discountCents = $this->calculateDiscount($condition, $amountCents);
        $afterDiscount = $amountCents - $discountCents;

        // 2. Número de parcelas
        $count = $condition->is_variable && $customInstallmentCount !== null
            ? max(1, $customInstallmentCount)
            : max(1, (int) $condition->installment_count);

        // 3. Juros de parcelamento
        $interestCents = $this->calculateInterest($condition, $afterDiscount, $count);
        $totalCents    = $afterDiscount + $interestCents;

        // 4. Gerar parcelas
        $installments = $this->buildInstallments($condition, $totalCents, $count, $saleDate);

        return [
            'original_amount_cents' => $amountCents,
            'discount_cents'        => $discountCents,
            'interest_cents'        => $interestCents,
            'total_amount_cents'    => $totalCents,
            'installments'          => $installments,
        ];
    }

    private function calculateDiscount(PaymentCondition $condition, int $amountCents): int
    {
        return match ($condition->discount_type) {
            'percent' => (int) round($amountCents * ((float) $condition->discount_value / 100)),
            'fixed'   => min($amountCents, (int) round((float) $condition->discount_value * 100)),
            default   => 0,
        };
    }

    private function calculateInterest(PaymentCondition $condition, int $baseAmount, int $count): int
    {
        $rate = (float) $condition->interest_value;

        if ($rate <= 0) {
            return 0;
        }

        return match ($condition->interest_type) {
            'percent_total'            => (int) round($baseAmount * ($rate / 100)),
            'percent_month'            => (int) round($baseAmount * ($rate / 100) * $count),
            'fixed_total'              => (int) round($rate * 100),
            'fixed_per_installment'    => (int) round($rate * 100 * $count),
            default                    => 0,
        };
    }

    /**
     * Gera o array de parcelas com vencimentos calculados.
     *
     * Regras de vencimento:
     *   - has_entry=true: parcela 1 vence na data da venda (entrada no ato)
     *                     parcela 2..N vencem com intervalo a partir da venda
     *   - has_entry=false: parcela 1 vence em saleDate + first_due_days
     *                      parcela N vence em saleDate + first_due_days + (N-1)*interval_days
     *
     * Distribuição de centavos: resto vai na última parcela.
     *
     * @return array<array{number: int, due_date: string, amount_cents: int}>
     */
    private function buildInstallments(
        PaymentCondition $condition,
        int $totalCents,
        int $count,
        Carbon $saleDate,
    ): array {
        $base      = (int) floor($totalCents / $count);
        $remainder = $totalCents - ($base * $count);

        $installments    = [];
        $firstDueDays    = (int) $condition->first_due_days;
        $intervalDays    = max(1, (int) $condition->interval_days);
        $hasEntry        = (bool) $condition->has_entry;

        for ($i = 1; $i <= $count; $i++) {
            $amount = $base + ($i === $count ? $remainder : 0);

            if ($hasEntry) {
                $dueDate = $i === 1
                    ? $saleDate->clone()
                    : $saleDate->clone()->addDays(($i - 1) * $intervalDays);
            } else {
                $dueDate = $saleDate->clone()->addDays($firstDueDays + ($i - 1) * $intervalDays);
            }

            $installments[] = [
                'number'      => $i,
                'due_date'    => $dueDate->toDateString(),
                'amount_cents' => $amount,
            ];
        }

        return $installments;
    }
}
