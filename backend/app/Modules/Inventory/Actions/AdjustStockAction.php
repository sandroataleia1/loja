<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Inventory\DTOs\AdjustStockDTO;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Modules\Inventory\Events\StockAdjusted;
use App\Modules\Inventory\Models\InventoryAdjustment;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\StockMovement;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class AdjustStockAction
{
    /**
     * Ajusta o estoque de uma variante em uma loja.
     *
     * quantity positivo = entrada; negativo = saída.
     * Respeita a configuração allow_negative_stock do tenant.
     */
    public function execute(AdjustStockDTO $dto): InventoryBalance
    {
        return DB::transaction(function () use ($dto): InventoryBalance {
            // Pessimistic lock: apenas uma transação por vez por (store, variant)
            $balance = InventoryBalance::where('store_id', $dto->storeId)
                ->where('variant_id', $dto->variantId)
                ->lockForUpdate()
                ->first();

            if ($balance === null) {
                $balance = InventoryBalance::create([
                    'store_id'          => $dto->storeId,
                    'variant_id'        => $dto->variantId,
                    'quantity'          => 0,
                    'reserved_quantity' => 0,
                ]);
            }

            $quantityBefore = $balance->quantity;
            $newQuantity    = $balance->quantity + $dto->quantity;

            if ($newQuantity < 0 && ! $this->tenantAllowsNegative()) {
                throw new BusinessException(
                    "Estoque insuficiente: disponível {$balance->quantity}, tentativa de saída {$dto->quantity}."
                );
            }

            $balance->applyQuantityChange($dto->quantity);

            $movement = StockMovement::create([
                'store_id'       => $dto->storeId,
                'variant_id'     => $dto->variantId,
                'type'           => $dto->type,
                'quantity'       => $dto->quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after'  => $newQuantity,
                'reserved_before' => $balance->reserved_quantity,
                'reserved_after'  => $balance->reserved_quantity,
                'reference_type' => $dto->referenceType,
                'reference_id'   => $dto->referenceId,
                'created_by'     => Auth::id(),
                'notes'          => $dto->notes,
                'metadata'       => $dto->metadata,
            ]);

            $adjustmentTypes = [
                MovementTypeEnum::Adjustment,
                MovementTypeEnum::Loss,
                MovementTypeEnum::Purchase,
                MovementTypeEnum::In,
                MovementTypeEnum::Out,
                MovementTypeEnum::Inventory,
            ];

            if (in_array($dto->type, $adjustmentTypes, strict: true)) {
                $tenantId = TenantContext::getIdOrFail();
                InventoryAdjustment::create([
                    'tenant_id'         => $tenantId,
                    'store_id'          => $dto->storeId,
                    'variant_id'        => $dto->variantId,
                    'movement_id'       => $movement->uuid,
                    'previous_quantity' => $quantityBefore,
                    'new_quantity'      => $balance->quantity,
                    'reason'            => $dto->notes ?? $dto->type->label(),
                    'created_by'        => Auth::id(),
                ]);
            }

            StockAdjusted::dispatch($balance->fresh(), $movement);

            return $balance->fresh();
        });
    }

    private function tenantAllowsNegative(): bool
    {
        /** @var \App\Core\Tenancy\Models\Tenant|null $tenant */
        $tenant = Tenant::find(TenantContext::getIdOrFail());

        return (bool) ($tenant?->settings['allow_negative_stock'] ?? false);
    }
}
