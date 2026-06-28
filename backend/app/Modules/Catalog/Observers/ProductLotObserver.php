<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Observers;

use App\Modules\Catalog\Models\ProductLot;
use Illuminate\Support\Facades\Log;

final class ProductLotObserver
{
    public function created(ProductLot $lot): void
    {
        if ($lot->expiry_date !== null && $lot->isExpiringSoon(30)) {
            Log::info("Lote {$lot->lot_number} vence em {$lot->expiresInDays()} dias", [
                'lot_uuid'    => $lot->uuid,
                'product_id'  => $lot->product_id,
                'expiry_date' => $lot->expiry_date?->toDateString(),
            ]);
        }
    }
}
