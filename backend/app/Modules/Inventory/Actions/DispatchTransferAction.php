<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Modules\Inventory\DTOs\DispatchTransferDTO;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Modules\Inventory\Enums\TransferStatusEnum;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockTransfer;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class DispatchTransferAction
{
    /**
     * Despacha a transferência: debita estoque na loja de origem e muda status para in_transit.
     */
    public function execute(StockTransfer $transfer, DispatchTransferDTO $dto): StockTransfer
    {
        if (! $transfer->status->canDispatch()) {
            throw new BusinessException("Transferência não pode ser despachada no status '{$transfer->status->label()}'.");
        }

        return DB::transaction(function () use ($transfer, $dto): StockTransfer {
            $itemMap = collect($dto->items)->keyBy('variant_id');

            foreach ($transfer->items as $item) {
                $sent = $itemMap[$item->variant_id]['quantity_sent'] ?? $item->quantity_requested;

                // Lock e debita saldo na origem
                $balance = InventoryBalance::where('store_id', $transfer->origin_store_id)
                    ->where('variant_id', $item->variant_id)
                    ->lockForUpdate()
                    ->first();

                $qtyBefore = $balance?->quantity ?? 0;
                $qtyAfter  = $qtyBefore - $sent;

                if ($balance) {
                    $balance->applyQuantityChange(-$sent);
                }

                StockMovement::create([
                    'store_id'        => $transfer->origin_store_id,
                    'variant_id'      => $item->variant_id,
                    'type'            => MovementTypeEnum::Transfer,
                    'quantity'        => -$sent,
                    'quantity_before' => $qtyBefore,
                    'quantity_after'  => $qtyAfter,
                    'reserved_before' => $balance?->reserved_quantity ?? 0,
                    'reserved_after'  => $balance?->reserved_quantity ?? 0,
                    'reference_type'  => 'stock_transfer',
                    'reference_id'    => $transfer->uuid,
                    'created_by'      => Auth::id(),
                ]);

                $item->update(['quantity_sent' => $sent]);
            }

            $transfer->update([
                'status'        => TransferStatusEnum::InTransit,
                'dispatched_by' => Auth::id(),
                'dispatched_at' => now(),
                'notes'         => $dto->notes ?? $transfer->notes,
            ]);

            return $transfer->fresh()->load(['originStore', 'destinationStore', 'items.variant']);
        });
    }
}
