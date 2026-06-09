<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Modules\Inventory\DTOs\CommitStockCountDTO;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Modules\Inventory\Enums\StockCountStatusEnum;
use App\Modules\Inventory\Events\InventoryCounted;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\StockCount;
use App\Modules\Inventory\Models\StockMovement;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CommitStockCountAction
{
    /**
     * Confirma o inventário físico: aplica as contagens, gera movimentos de ajuste
     * para cada variante com divergência e fecha o inventário.
     */
    public function execute(StockCount $count, CommitStockCountDTO $dto): StockCount
    {
        if (! $count->status->canCommit()) {
            throw new BusinessException("Inventário não pode ser confirmado no status '{$count->status->label()}'.");
        }

        return DB::transaction(function () use ($count, $dto): StockCount {
            $itemMap = collect($dto->items)->keyBy('variant_id');

            foreach ($count->items as $countItem) {
                $counted = $itemMap[$countItem->variant_id]['counted_quantity']
                    ?? throw new BusinessException("Contagem não informada para variante {$countItem->variant_id}.");

                $countItem->update(['counted_quantity' => $counted]);

                $diff = $counted - $countItem->system_quantity;
                if ($diff === 0) {
                    continue;
                }

                // Ajusta o saldo real com lock
                $balance = InventoryBalance::where('store_id', $count->store_id)
                    ->where('variant_id', $countItem->variant_id)
                    ->lockForUpdate()
                    ->first();

                $qtyBefore = $balance?->quantity ?? 0;
                $qtyAfter  = $counted;

                if ($balance) {
                    $balance->applyQuantityChange($qtyAfter - $qtyBefore);
                } else {
                    $balance = InventoryBalance::create([
                        'store_id'   => $count->store_id,
                        'variant_id' => $countItem->variant_id,
                    ]);
                    $balance->applyQuantityChange($qtyAfter);
                }

                StockMovement::create([
                    'store_id'        => $count->store_id,
                    'variant_id'      => $countItem->variant_id,
                    'type'            => MovementTypeEnum::Inventory,
                    'quantity'        => $diff,
                    'quantity_before' => $qtyBefore,
                    'quantity_after'  => $qtyAfter,
                    'reserved_before' => $balance?->reserved_quantity ?? 0,
                    'reserved_after'  => $balance?->reserved_quantity ?? 0,
                    'reference_type'  => 'stock_count',
                    'reference_id'    => $count->uuid,
                    'created_by'      => Auth::id(),
                    'notes'           => "Ajuste de inventário físico (diferença: {$diff}).",
                ]);
            }

            $count->update([
                'status'       => StockCountStatusEnum::Committed,
                'committed_by' => Auth::id(),
                'committed_at' => now(),
                'notes'        => $dto->notes ?? $count->notes,
            ]);

            $updated = $count->fresh()->load(['store', 'items.variant', 'creator', 'committer']);

            InventoryCounted::dispatch($updated);

            return $updated;
        });
    }
}
