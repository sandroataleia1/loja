<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Enums\FinancialEntryStatusEnum;
use App\Modules\Finance\Enums\FinancialEntryTypeEnum;
use App\Modules\Finance\Enums\ReconciliationStatusEnum;
use App\Modules\Finance\Events\FinancialEntryCreated;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Purchasing\Models\PurchaseReceipt;

final readonly class CreatePayableFromPurchaseAction
{
    /**
     * Cria um título a pagar (FinancialEntry/Expense) a partir de um recebimento
     * de compra.
     *
     * Valor = Σ (quantity_received × unit_cost) dos itens do recebimento.
     * unit_cost é decimal (reais) → convertido para centavos.
     *
     * Idempotência: um título por recebimento (reference = purchase_receipt).
     * Recebimentos parciais geram um título cada — espelhando o pagamento por
     * entrega.
     */
    public function execute(PurchaseReceipt $receipt): ?FinancialEntry
    {
        $receipt->loadMissing(['items', 'order']);

        $order = $receipt->order;

        if ($order === null) {
            return null;
        }

        $totalCents = (int) round(
            $receipt->items->sum(
                fn ($item): float => $item->quantity_received * (float) $item->unit_cost
            ) * 100
        );

        if ($totalCents <= 0) {
            return null;
        }

        $entry = FinancialEntry::create([
            'store_id'              => $order->store_id,
            'type'                  => FinancialEntryTypeEnum::Expense,
            'category_id'           => null,
            'financial_account_id'  => null,
            'amount_cents'          => $totalCents,
            'due_date'              => now()->toDateString(),
            'paid_at'               => null,
            'status'                => FinancialEntryStatusEnum::Pending,
            'description'           => "Compra {$order->code} — recebimento",
            'reference_type'        => 'purchase_receipt',
            'reference_id'          => $receipt->uuid,
            'reconciliation_status' => ReconciliationStatusEnum::Pending,
            'external_reference'    => null,
        ]);

        FinancialEntryCreated::dispatch($entry);

        return $entry;
    }
}
