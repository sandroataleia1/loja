<?php

declare(strict_types=1);

namespace App\Modules\Finance\Listeners;

use App\Modules\Finance\Actions\CancelFinancialEntryAction;
use App\Modules\Finance\Enums\FinancialEntryStatusEnum;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Sales\Events\SaleCancelled;

/**
 * Estorna as contas a receber de uma venda cancelada.
 *
 * Espelha CreateFinancialEntryOnSaleCompleted: o Finance observa o cancelamento
 * e cancela (revertendo saldo de conta, se houver) os FinancialEntry gerados na
 * conclusão da venda. Mantém o domínio de vendas desacoplado do financeiro.
 */
final readonly class CancelFinancialEntriesOnSaleCancelled
{
    public function __construct(
        private CancelFinancialEntryAction $action,
    ) {}

    public function handle(SaleCancelled $event): void
    {
        $entries = FinancialEntry::where('reference_type', 'sale')
            ->where('reference_id', $event->sale->uuid)
            ->where('status', '!=', FinancialEntryStatusEnum::Cancelled->value)
            ->get();

        foreach ($entries as $entry) {
            if ($entry->status->canCancel()) {
                $this->action->execute($entry, 'Venda cancelada');
            }
        }
    }
}
