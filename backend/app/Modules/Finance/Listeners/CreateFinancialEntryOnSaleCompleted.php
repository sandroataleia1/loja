<?php

declare(strict_types=1);

namespace App\Modules\Finance\Listeners;

use App\Modules\Finance\Actions\CreateFinancialEntryFromSaleAction;
use App\Modules\Sales\Events\SaleCompleted;

final readonly class CreateFinancialEntryOnSaleCompleted
{
    public function __construct(
        private CreateFinancialEntryFromSaleAction $action,
    ) {}

    public function handle(SaleCompleted $event): void
    {
        $sale = $event->sale;
        $sale->loadMissing('payments');

        $this->action->execute($sale);
    }
}
