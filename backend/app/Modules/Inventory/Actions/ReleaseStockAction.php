<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Modules\Inventory\DTOs\ReleaseStockDTO;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Modules\Inventory\Events\StockReleased;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\StockMovement;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class ReleaseStockAction
{
    /**
     * Libera uma reserva (reduz reserved_quantity sem alterar quantity).
     * Chamado quando um pedido é cancelado ou expirado.
     */
    public function execute(ReleaseStockDTO $dto): InventoryBalance
    {
        return DB::transaction(function () use ($dto): InventoryBalance {
            $balance = InventoryBalance::where('store_id', $dto->storeId)
                ->where('variant_id', $dto->variantId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($dto->quantity > $balance->reserved_quantity) {
                throw new BusinessException(
                    "Não é possível liberar {$dto->quantity} unidades: apenas {$balance->reserved_quantity} estão reservadas."
                );
            }

            $reservedBefore = $balance->reserved_quantity;
            $reservedAfter  = $reservedBefore - $dto->quantity;

            $balance->applyQuantityChange(0, -$dto->quantity);

            $movement = StockMovement::create([
                'store_id'        => $dto->storeId,
                'variant_id'      => $dto->variantId,
                'type'            => MovementTypeEnum::Release,
                'quantity'        => $dto->quantity,
                'quantity_before' => $balance->quantity,
                'quantity_after'  => $balance->quantity,
                'reserved_before' => $reservedBefore,
                'reserved_after'  => $reservedAfter,
                'reference_type'  => $dto->referenceType,
                'reference_id'    => $dto->referenceId,
                'created_by'      => Auth::id(),
            ]);

            StockReleased::dispatch($balance->fresh(), $movement);

            return $balance->fresh();
        });
    }
}
