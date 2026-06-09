<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Modules\Inventory\DTOs\ReceiveTransferDTO;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Modules\Inventory\Enums\TransferStatusEnum;
use App\Modules\Inventory\Events\StockTransferred;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockTransfer;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class ReceiveTransferAction
{
    /**
     * Recebe a transferência: credita estoque na loja de destino e muda status para received.
     * Divergências entre quantity_sent e quantity_received são registradas no item.
     */
    public function execute(StockTransfer $transfer, ReceiveTransferDTO $dto): StockTransfer
    {
        if (! $transfer->status->canReceive()) {
            throw new BusinessException("Transferência não pode ser recebida no status '{$transfer->status->label()}'.");
        }

        return DB::transaction(function () use ($transfer, $dto): StockTransfer {
            $itemMap = collect($dto->items)->keyBy('variant_id');

            foreach ($transfer->items as $item) {
                $received = $itemMap[$item->variant_id]['quantity_received'] ?? ($item->quantity_sent ?? $item->quantity_requested);

                // Cria ou incrementa saldo na loja de destino
                $balance = InventoryBalance::where('store_id', $transfer->destination_store_id)
                    ->where('variant_id', $item->variant_id)
                    ->lockForUpdate()
                    ->first();

                if ($balance === null) {
                    $balance = InventoryBalance::create([
                        'store_id'  => $transfer->destination_store_id,
                        'variant_id' => $item->variant_id,
                    ]);
                }

                $qtyBefore = $balance->quantity;
                $qtyAfter  = $qtyBefore + $received;
                $balance->applyQuantityChange($received);

                StockMovement::create([
                    'store_id'        => $transfer->destination_store_id,
                    'variant_id'      => $item->variant_id,
                    'type'            => MovementTypeEnum::Purchase,
                    'quantity'        => $received,
                    'quantity_before' => $qtyBefore,
                    'quantity_after'  => $qtyAfter,
                    'reserved_before' => $balance->reserved_quantity,
                    'reserved_after'  => $balance->reserved_quantity,
                    'reference_type'  => 'stock_transfer',
                    'reference_id'    => $transfer->uuid,
                    'created_by'      => Auth::id(),
                ]);

                $item->update(['quantity_received' => $received]);
            }

            $transfer->update([
                'status'      => TransferStatusEnum::Received,
                'received_by' => Auth::id(),
                'received_at' => now(),
                'notes'       => $dto->notes ?? $transfer->notes,
            ]);

            $updated = $transfer->fresh()->load(['originStore', 'destinationStore', 'items.variant']);

            StockTransferred::dispatch($updated);

            return $updated;
        });
    }
}
