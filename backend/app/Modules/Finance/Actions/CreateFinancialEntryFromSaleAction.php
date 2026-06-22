<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Enums\FinancialEntryStatusEnum;
use App\Modules\Finance\Enums\FinancialEntryTypeEnum;
use App\Modules\Finance\Enums\ReconciliationStatusEnum;
use App\Modules\Finance\Events\FinancialEntryCreated;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Models\FinancialInstallment;
use App\Modules\Sales\Models\Sale;

final readonly class CreateFinancialEntryFromSaleAction
{
    /**
     * Cria um FinancialEntry por PaymentTransaction quando uma venda é concluída.
     *
     * Se a transação possui parcelas (total_installments > 1), cria FinancialInstallment
     * individuais com os vencimentos calculados pelo InstallmentCalculatorService.
     *
     * @return FinancialEntry[]
     */
    public function execute(Sale $sale): array
    {
        $entries = [];

        foreach ($sale->payments as $payment) {
            $isInstant  = $payment->method->isInstant();
            $dueDate    = $payment->due_date?->toDateString()
                ?? $sale->completed_at?->toDateString()
                ?? now()->toDateString();

            $methodLabel = $payment->paymentMethod?->name ?? $payment->method->label();

            $entry = FinancialEntry::create([
                'store_id'              => $sale->store_id,
                'type'                  => FinancialEntryTypeEnum::Income,
                'category_id'           => null,
                'financial_account_id'  => null,
                'amount_cents'          => $payment->amount_cents,
                'due_date'              => $dueDate,
                'paid_at'               => $isInstant ? ($sale->completed_at ?? now()) : null,
                'status'                => $isInstant
                    ? FinancialEntryStatusEnum::Paid
                    : FinancialEntryStatusEnum::Pending,
                'description'           => "Venda {$sale->code} — {$methodLabel}"
                    . ($payment->total_installments > 1
                        ? " ({$payment->installment_number}/{$payment->total_installments})"
                        : ''),
                'reference_type'        => 'sale',
                'reference_id'          => $sale->uuid,
                'reconciliation_status' => ReconciliationStatusEnum::Pending,
                'external_reference'    => $payment->external_reference,
            ]);

            FinancialEntryCreated::dispatch($entry);

            $entries[] = $entry;
        }

        return $entries;
    }
}
