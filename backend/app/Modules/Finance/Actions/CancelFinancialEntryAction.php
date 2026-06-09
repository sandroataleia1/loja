<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Enums\FinancialEntryStatusEnum;
use App\Modules\Finance\Enums\FinancialInstallmentStatusEnum;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialEntry;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;

final readonly class CancelFinancialEntryAction
{
    /**
     * Cancela lançamento.
     *
     * Se o lançamento (ou parcelas) já havia impactado saldo de conta,
     * o cancelamento reverte o saldo automaticamente.
     * O registro nunca é deletado.
     */
    public function execute(FinancialEntry $entry, ?string $reason = null): FinancialEntry
    {
        if (! $entry->status->canCancel()) {
            throw new BusinessException(
                "Lançamento não pode ser cancelado no status '{$entry->status->label()}'."
            );
        }

        return DB::transaction(function () use ($entry, $reason): FinancialEntry {
            // Reverte o saldo se o lançamento (todo ou parcialmente) havia sido pago
            if ($entry->financial_account_id !== null) {
                $paidAmount = $this->computePaidAmount($entry);

                if ($paidAmount > 0) {
                    // Sinal inverso: desfaz o impacto original
                    FinancialAccount::where('uuid', $entry->financial_account_id)
                        ->lockForUpdate()
                        ->firstOrFail()
                        ->increment('current_balance_cents', -($entry->type->balanceSign() * $paidAmount));
                }
            }

            // Cancela parcelas pendentes/vencidas
            $entry->installments()
                ->whereNotIn('status', [
                    FinancialInstallmentStatusEnum::Paid->value,
                    FinancialInstallmentStatusEnum::Cancelled->value,
                ])
                ->update(['status' => FinancialInstallmentStatusEnum::Cancelled->value]);

            $entry->update([
                'status'      => FinancialEntryStatusEnum::Cancelled,
                'description' => $entry->description
                    ? $entry->description . ($reason ? " [Cancelado: {$reason}]" : ' [Cancelado]')
                    : ($reason ? "Cancelado: {$reason}" : 'Cancelado'),
            ]);

            return $entry->fresh()->load(['installments', 'account', 'category']);
        });
    }

    private function computePaidAmount(FinancialEntry $entry): int
    {
        if ($entry->status === FinancialEntryStatusEnum::Paid) {
            return $entry->amount_cents;
        }

        // Parcialmente pago: soma apenas parcelas já pagas
        return (int) $entry->installments()
            ->where('status', FinancialInstallmentStatusEnum::Paid->value)
            ->sum('amount_cents');
    }
}
