<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Events;

use App\Modules\Purchasing\Models\PurchaseReceipt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Emitido quando um recebimento de compra é registrado (total ou parcial).
 *
 * Cada recebimento gera um título a pagar (FinancialEntry/Expense) referente ao
 * que foi efetivamente recebido — mantendo o domínio financeiro desacoplado do
 * domínio de compras.
 */
final class PurchaseReceived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly PurchaseReceipt $receipt,
    ) {}
}
