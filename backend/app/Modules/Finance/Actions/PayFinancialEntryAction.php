<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\DTOs\PayFinancialEntryDTO;
use App\Modules\Finance\Enums\FinancialEntryStatusEnum;
use App\Modules\Finance\Enums\FinancialInstallmentStatusEnum;
use App\Modules\Finance\Events\FinancialEntryPaid;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialEntry;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;

final readonly class PayFinancialEntryAction
{
    /**
     * Quita um lançamento ou uma parcela específica.
     *
     * Quando installment_number fornecido: paga apenas aquela parcela.
     *   - Verifica se todas pagas → entry vira PAID
     *   - Caso contrário → entry vira PARTIALLY_PAID
     *
     * Sem installment_number: quita o lançamento inteiro.
     *
     * Atualiza saldo da conta financeira com lockForUpdate.
     */
    public function execute(FinancialEntry $entry, PayFinancialEntryDTO $dto): FinancialEntry
    {
        if (! $entry->status->canPay()) {
            throw new BusinessException(
                "Lançamento não pode ser quitado no status '{$entry->status->label()}'."
            );
        }

        return DB::transaction(function () use ($entry, $dto): FinancialEntry {
            $accountId = $dto->financialAccountId ?? $entry->financial_account_id;

            if ($dto->installmentNumber !== null) {
                $amountPaid = $this->payInstallment($entry, $dto, $accountId);
            } else {
                $amountPaid = $this->payFull($entry, $dto, $accountId);
            }

            if ($accountId !== null) {
                $this->updateAccountBalance($accountId, $entry->type->balanceSign() * $amountPaid);
            }

            $entry->refresh()->load(['installments', 'account', 'category']);

            FinancialEntryPaid::dispatch($entry, $amountPaid);

            return $entry;
        });
    }

    private function payInstallment(FinancialEntry $entry, PayFinancialEntryDTO $dto, ?string $accountId): int
    {
        $installment = $entry->installments()
            ->where('installment_number', $dto->installmentNumber)
            ->lockForUpdate()
            ->firstOrFail();

        if (! $installment->status->canPay()) {
            throw new BusinessException(
                "Parcela {$dto->installmentNumber} não pode ser quitada no status '{$installment->status->label()}'."
            );
        }

        $amount = $dto->amountCents ?? $installment->amount_cents;

        $installment->update([
            'status'  => FinancialInstallmentStatusEnum::Paid,
            'paid_at' => $dto->paidAt,
        ]);

        // Atualiza status do lançamento pai
        $remaining = $entry->installments()
            ->whereNotIn('status', [
                FinancialInstallmentStatusEnum::Paid->value,
                FinancialInstallmentStatusEnum::Cancelled->value,
            ])
            ->count();

        $newStatus = $remaining === 0
            ? FinancialEntryStatusEnum::Paid
            : FinancialEntryStatusEnum::PartiallyPaid;

        $entry->update([
            'status'               => $newStatus,
            'financial_account_id' => $accountId ?? $entry->financial_account_id,
            'paid_at'              => $remaining === 0 ? $dto->paidAt : $entry->paid_at,
        ]);

        return $amount;
    }

    private function payFull(FinancialEntry $entry, PayFinancialEntryDTO $dto, ?string $accountId): int
    {
        $amount = $dto->amountCents ?? $entry->amount_cents;

        $entry->update([
            'status'               => FinancialEntryStatusEnum::Paid,
            'financial_account_id' => $accountId ?? $entry->financial_account_id,
            'paid_at'              => $dto->paidAt,
        ]);

        // Marca todas parcelas pendentes como pagas
        $entry->installments()
            ->whereIn('status', [
                FinancialInstallmentStatusEnum::Pending->value,
                FinancialInstallmentStatusEnum::Overdue->value,
            ])
            ->update([
                'status'  => FinancialInstallmentStatusEnum::Paid->value,
                'paid_at' => $dto->paidAt,
            ]);

        return $amount;
    }

    private function updateAccountBalance(string $accountId, int $signedAmountCents): void
    {
        FinancialAccount::where('uuid', $accountId)
            ->lockForUpdate()
            ->firstOrFail()
            ->increment('current_balance_cents', $signedAmountCents);
    }
}
