<?php

declare(strict_types=1);

namespace App\Modules\Finance\Listeners;

use App\Modules\Finance\Actions\CreatePayableFromPurchaseAction;
use App\Modules\Purchasing\Events\PurchaseReceived;

/**
 * Gera contas a pagar quando uma compra é recebida.
 *
 * Mantém o módulo Purchasing desacoplado do Finance: é o Finance que observa o
 * evento de recebimento e cria o título. Espelha
 * CreateFinancialEntryOnSaleCompleted (venda → contas a receber).
 */
final readonly class CreatePayableOnPurchaseReceived
{
    public function __construct(
        private CreatePayableFromPurchaseAction $action,
    ) {}

    public function handle(PurchaseReceived $event): void
    {
        $this->action->execute($event->receipt);
    }
}
