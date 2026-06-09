<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Listeners;

use App\Modules\Inventory\Events\StockAdjusted;

/**
 * Stub — notifica usuários quando o estoque disponível cai abaixo de um limiar configurável.
 * Implementação futura: configurar limiar por tenant/loja/variante e disparar notificação.
 */
final class NotifyLowStock
{
    public function handle(StockAdjusted $event): void
    {
        $balance = $event->balance;

        if ($balance->available_quantity <= 0) {
            // TODO: dispatch LowStockNotification via queue 'notifications'
        }
    }
}
