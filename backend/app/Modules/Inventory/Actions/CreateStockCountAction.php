<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Modules\Inventory\DTOs\CreateStockCountDTO;
use App\Modules\Inventory\Enums\StockCountStatusEnum;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\StockCount;
use App\Modules\Inventory\Models\StockCountItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateStockCountAction
{
    /**
     * Cria um inventário físico com snapshot do saldo atual de cada variante.
     * Se variantIds for null, inclui todas as variantes com saldo na loja.
     */
    public function execute(CreateStockCountDTO $dto): StockCount
    {
        return DB::transaction(function () use ($dto): StockCount {
            $count = StockCount::create([
                'store_id'   => $dto->storeId,
                'status'     => StockCountStatusEnum::InProgress,
                'notes'      => $dto->notes,
                'created_by' => Auth::id(),
                'started_at' => now(),
            ]);

            $balanceQuery = InventoryBalance::where('store_id', $dto->storeId);

            if ($dto->variantIds !== null) {
                $balanceQuery->whereIn('variant_id', $dto->variantIds);
            }

            foreach ($balanceQuery->get() as $balance) {
                StockCountItem::create([
                    'count_id'        => $count->uuid,
                    'variant_id'      => $balance->variant_id,
                    'system_quantity' => $balance->quantity,
                ]);
            }

            return $count->load(['store', 'items.variant', 'creator']);
        });
    }
}
