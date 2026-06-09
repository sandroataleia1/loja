<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Modules\Inventory\Enums\TransferStatusEnum;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockTransfer;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CancelTransferAction
{
    /**
     * Cancela uma transferência.
     * Se já estava InTransit, estorna o débito na origem (cria movimento de retorno).
     */
    public function execute(StockTransfer $transfer): StockTransfer
    {
        if (! $transfer->status->canCancel()) {
            throw new BusinessException("Transferência não pode ser cancelada no status '{$transfer->status->label()}'.");
        }

        return DB::transaction(function () use ($transfer): StockTransfer {
            // Estorna débito se já havia sido despachada
            if ($transfer->status === TransferStatusEnum::InTransit) {
                foreach ($transfer->items as $item) {
                    $sent = $item->quantity_sent ?? 0;
                    if ($sent === 0) {
                        continue;
                    }

                    $balance = InventoryBalance::where('store_id', $transfer->origin_store_id)
                        ->where('variant_id', $item->variant_id)
                        ->lockForUpdate()
                        ->first();

                    $qtyBefore = $balance?->quantity ?? 0;
                    $qtyAfter  = $qtyBefore + $sent;

                    if ($balance) {
                        $balance->applyQuantityChange($sent);
                    } else {
                        $balance = InventoryBalance::create([
                            'store_id'   => $transfer->origin_store_id,
                            'variant_id' => $item->variant_id,
                        ]);
                        $balance->applyQuantityChange($sent);
                    }

                    StockMovement::create([
                        'store_id'        => $transfer->origin_store_id,
                        'variant_id'      => $item->variant_id,
                        'type'            => MovementTypeEnum::Return,
                        'quantity'        => $sent,
                        'quantity_before' => $qtyBefore,
                        'quantity_after'  => $qtyAfter,
                        'reserved_before' => $balance?->reserved_quantity ?? 0,
                        'reserved_after'  => $balance?->reserved_quantity ?? 0,
                        'reference_type'  => 'stock_transfer',
                        'reference_id'    => $transfer->uuid,
                        'created_by'      => Auth::id(),
                        'notes'           => 'Estorno por cancelamento de transferência.',
                    ]);
                }
            }

            $transfer->update([
                'status'       => TransferStatusEnum::Cancelled,
                'cancelled_at' => now(),
            ]);

            return $transfer->fresh()->load(['originStore', 'destinationStore', 'items.variant']);
        });
    }
}
