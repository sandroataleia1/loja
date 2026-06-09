<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Actions\AdjustStockAction;
use App\Modules\Inventory\Actions\ReserveStockAction;
use App\Modules\Inventory\Actions\ReleaseStockAction;
use App\Modules\Inventory\DTOs\AdjustStockDTO;
use App\Modules\Inventory\DTOs\ReserveStockDTO;
use App\Modules\Inventory\DTOs\ReleaseStockDTO;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Modules\Inventory\Models\InventoryBalance;

final class InventoryService
{
    public function __construct(
        private readonly AdjustStockAction  $adjustAction,
        private readonly ReserveStockAction $reserveAction,
        private readonly ReleaseStockAction $releaseAction,
    ) {}

    /** Receive stock into a store (entry). */
    public function receive(string $storeId, string $variantId, int $quantity, string $referenceType = 'manual', ?string $referenceId = null, ?string $notes = null): InventoryBalance
    {
        return $this->adjustAction->execute(new AdjustStockDTO(
            storeId: $storeId, variantId: $variantId,
            quantity: abs($quantity), type: MovementTypeEnum::In,
            notes: $notes, referenceType: $referenceType,
            referenceId: $referenceId, metadata: null,
        ));
    }

    /** Adjust stock by a relative amount (positive = add, negative = remove). */
    public function adjust(string $storeId, string $variantId, int $quantity, string $reason, ?string $notes = null): InventoryBalance
    {
        return $this->adjustAction->execute(new AdjustStockDTO(
            storeId: $storeId, variantId: $variantId,
            quantity: $quantity, type: MovementTypeEnum::Adjustment,
            notes: $notes ?? $reason, referenceType: 'adjustment',
            referenceId: null, metadata: null,
        ));
    }

    /** Reserve stock (reduces availability, not current_stock). */
    public function reserve(string $storeId, string $variantId, int $quantity, string $sourceType, string $sourceId): InventoryBalance
    {
        return $this->reserveAction->execute(new ReserveStockDTO(
            storeId: $storeId, variantId: $variantId,
            quantity: $quantity, referenceType: $sourceType, referenceId: $sourceId,
        ));
    }

    /** Release a reservation. */
    public function release(string $storeId, string $variantId, int $quantity, string $sourceType, string $sourceId): InventoryBalance
    {
        return $this->releaseAction->execute(new ReleaseStockDTO(
            storeId: $storeId, variantId: $variantId,
            quantity: $quantity, referenceType: $sourceType, referenceId: $sourceId,
        ));
    }

    /** Consume stock (sale, conditional exit). */
    public function consume(string $storeId, string $variantId, int $quantity, string $referenceType, string $referenceId, MovementTypeEnum $type = MovementTypeEnum::Sale): InventoryBalance
    {
        return $this->adjustAction->execute(new AdjustStockDTO(
            storeId: $storeId, variantId: $variantId,
            quantity: -abs($quantity), type: $type,
            notes: null, referenceType: $referenceType,
            referenceId: $referenceId, metadata: null,
        ));
    }

    /** Return stock to inventory. */
    public function return(string $storeId, string $variantId, int $quantity, string $referenceType, string $referenceId): InventoryBalance
    {
        return $this->adjustAction->execute(new AdjustStockDTO(
            storeId: $storeId, variantId: $variantId,
            quantity: abs($quantity), type: MovementTypeEnum::Return,
            notes: null, referenceType: $referenceType,
            referenceId: $referenceId, metadata: null,
        ));
    }

    /** Get current balance for a variant at a store. Returns null if no record exists. */
    public function getBalance(string $storeId, string $variantId): ?InventoryBalance
    {
        return InventoryBalance::where('store_id', $storeId)
            ->where('variant_id', $variantId)
            ->first();
    }

    /** Check if available_quantity (quantity - reserved) meets the requested amount. */
    public function hasAvailable(string $storeId, string $variantId, int $quantity): bool
    {
        $balance = $this->getBalance($storeId, $variantId);

        return $balance !== null && $balance->available_quantity >= $quantity;
    }
}
