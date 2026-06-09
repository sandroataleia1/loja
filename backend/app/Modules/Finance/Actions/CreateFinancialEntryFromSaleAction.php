<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Enums\FinancialEntryStatusEnum;
use App\Modules\Finance\Enums\FinancialEntryTypeEnum;
use App\Modules\Finance\Enums\ReconciliationStatusEnum;
use App\Modules\Finance\Events\FinancialEntryCreated;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Sales\Models\Sale;

final readonly class CreateFinancialEntryFromSaleAction
{
    /**
     * Cria um FinancialEntry por PaymentTransaction quando uma venda é concluída.
     *
     * Métodos instantâneos (cash, pix, store_credit, voucher) → status PAID.
     * Métodos cartão (credit_card, debit_card) → status PENDING (aguarda liquidação).
     * financial_account_id fica null para reconciliação posterior.
     *
     * @return FinancialEntry[]
     */
    public function execute(Sale $sale): array
    {
        $entries = [];

        foreach ($sale->payments as $payment) {
            $isInstant = $payment->method->isInstant();

            $entry = FinancialEntry::create([
                'store_id'              => $sale->store_id,
                'type'                  => FinancialEntryTypeEnum::Income,
                'category_id'           => null,
                'financial_account_id'  => null,
                'amount_cents'          => $payment->amount_cents,
                'due_date'              => $sale->completed_at?->toDateString() ?? now()->toDateString(),
                'paid_at'               => $isInstant ? $sale->completed_at ?? now() : null,
                'status'                => $isInstant
                                              ? FinancialEntryStatusEnum::Paid
                                              : FinancialEntryStatusEnum::Pending,
                'description'           => "Venda {$sale->code} — {$payment->method->label()}",
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
