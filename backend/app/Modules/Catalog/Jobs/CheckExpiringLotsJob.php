<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Jobs;

use App\Modules\Catalog\Models\ProductLot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class CheckExpiringLotsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        ProductLot::expiring(30)
            ->with(['product', 'variant'])
            ->each(function (ProductLot $lot): void {
                Log::info(
                    "Lote expirando: {$lot->product->name} Lote {$lot->lot_number} vence em {$lot->expiresInDays()} dias",
                    [
                        'lot_uuid'    => $lot->uuid,
                        'product_id'  => $lot->product_id,
                        'expiry_date' => $lot->expiry_date?->toDateString(),
                    ],
                );
                // Criar notificação interna quando NotificationService existir
            });
    }
}
