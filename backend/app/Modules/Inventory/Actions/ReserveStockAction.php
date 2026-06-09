<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Modules\Inventory\DTOs\ReserveStockDTO;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Modules\Inventory\Events\StockReserved;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\StockMovement;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class ReserveStockAction
{
    /**
     * Reserva quantidade de estoque (aumenta reserved_quantity sem alterar quantity).
     * Valida que available_quantity >= quantidade desejada.
     */
    public function execute(ReserveStockDTO $dto): InventoryBalance
    {
        return DB::transaction(function () use ($dto): InventoryBalance {
            $balance = InventoryBalance::where('store_id', $dto->storeId)
                ->where('variant_id', $dto->variantId)
                ->lockForUpdate()
                ->firstOrFail();

            $available = $balance->quantity - $balance->reserved_quantity;

            if ($dto->quantity > $available) {
                throw new BusinessException(
                    "Estoque disponível insuficiente para reserva: disponível {$available}, solicitado {$dto->quantity}."
                );
            }

            $reservedBefore = $balance->reserved_quantity;
            $reservedAfter  = $reservedBefore + $dto->quantity;

            $balance->applyQuantityChange(0, $dto->quantity);

            $movement = StockMovement::create([
                'store_id'        => $dto->storeId,
                'variant_id'      => $dto->variantId,
                'type'            => MovementTypeEnum::Reserve,
                'quantity'        => $dto->quantity,
                'quantity_before' => $balance->quantity,
                'quantity_after'  => $balance->quantity,
                'reserved_before' => $reservedBefore,
                'reserved_after'  => $reservedAfter,
                'reference_type'  => $dto->referenceType,
                'reference_id'    => $dto->referenceId,
                'created_by'      => Auth::id(),
            ]);

            StockReserved::dispatch($balance->fresh(), $movement);

            return $balance->fresh();
        });
    }
}
