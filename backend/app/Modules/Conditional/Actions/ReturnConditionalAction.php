<?php

declare(strict_types=1);

namespace App\Modules\Conditional\Actions;

use App\Modules\Conditional\Enums\ConditionalStatusEnum;
use App\Modules\Conditional\Events\ConditionalPartiallyReturned;
use App\Modules\Conditional\Events\ConditionalReturned;
use App\Modules\Conditional\Models\Conditional;
use App\Modules\Conditional\Models\ConditionalItem;
use App\Modules\Conditional\Models\ConditionalStatusHistory;
use App\Modules\Inventory\Actions\AdjustStockAction;
use App\Modules\Inventory\DTOs\AdjustStockDTO;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class ReturnConditionalAction
{
    public function __construct(
        private AdjustStockAction $adjustStock,
    ) {}

    /**
     * @param  array<int, array{item_uuid: string, quantity: int}>  $returns
     */
    public function execute(Conditional $conditional, array $returns): Conditional
    {
        if (! $conditional->status->canReturn()) {
            throw new BusinessException(
                "Condicional não pode ser devolvido no status '{$conditional->status->label()}'."
            );
        }

        return DB::transaction(function () use ($conditional, $returns): Conditional {
            $conditional->load('items');

            foreach ($returns as $return) {
                /** @var ConditionalItem|null $item */
                $item = $conditional->items->firstWhere('uuid', $return['item_uuid']);

                if ($item === null) {
                    throw new BusinessException("Item '{$return['item_uuid']}' não pertence a este condicional.");
                }

                $returnQty = (int) $return['quantity'];

                if ($returnQty > $item->pendingQuantity()) {
                    throw new BusinessException(
                        "Quantidade de devolução ({$returnQty}) excede o pendente ({$item->pendingQuantity()}) para o item '{$item->uuid}'."
                    );
                }

                $item->increment('returned_quantity', $returnQty);

                // Return to inventory with ConditionalReturn movement type
                $this->adjustStock->execute(new AdjustStockDTO(
                    storeId:       $conditional->store_id,
                    variantId:     $item->variant_id,
                    quantity:      abs($returnQty),
                    type:          MovementTypeEnum::ConditionalReturn,
                    notes:         null,
                    referenceType: 'conditional',
                    referenceId:   $conditional->uuid,
                    metadata:      null,
                ));
            }

            // Refresh items after increments
            $conditional->load('items');

            // Recalculate and update status
            $previousStatus = $conditional->status;
            $newStatus      = $this->computeStatus($conditional);

            if ($newStatus !== $previousStatus) {
                $conditional->update(['status' => $newStatus]);

                ConditionalStatusHistory::create([
                    'conditional_id'  => $conditional->uuid,
                    'previous_status' => $previousStatus->value,
                    'current_status'  => $newStatus->value,
                    'changed_by'      => Auth::id(),
                    'changed_at'      => now(),
                ]);

                if ($newStatus === ConditionalStatusEnum::Returned) {
                    ConditionalReturned::dispatch($conditional->tenant_id, $conditional->uuid, Auth::id());
                } else {
                    ConditionalPartiallyReturned::dispatch($conditional->tenant_id, $conditional->uuid, Auth::id());
                }
            }

            return $conditional->fresh()->load(['items', 'statusHistory']);
        });
    }

    private function computeStatus(Conditional $conditional): ConditionalStatusEnum
    {
        $totalQty    = $conditional->items->sum('quantity');
        $returnedQty = $conditional->items->sum('returned_quantity');
        $soldQty     = $conditional->items->sum('sold_quantity');

        if ($returnedQty + $soldQty === 0) {
            return $conditional->isOverdue()
                ? ConditionalStatusEnum::Overdue
                : ConditionalStatusEnum::Open;
        }

        $fullySettled = ($returnedQty + $soldQty === $totalQty);

        if ($fullySettled) {
            return $soldQty === 0
                ? ConditionalStatusEnum::Returned
                : ($returnedQty === 0
                    ? ConditionalStatusEnum::Converted
                    : ConditionalStatusEnum::PartiallyConverted);
        }

        // Partially settled
        return $soldQty > 0
            ? ConditionalStatusEnum::PartiallyConverted
            : ConditionalStatusEnum::PartiallyReturned;
    }
}
