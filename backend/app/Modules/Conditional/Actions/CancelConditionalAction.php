<?php

declare(strict_types=1);

namespace App\Modules\Conditional\Actions;

use App\Modules\Conditional\Enums\ConditionalStatusEnum;
use App\Modules\Conditional\Events\ConditionalCancelled;
use App\Modules\Conditional\Models\Conditional;
use App\Modules\Conditional\Models\ConditionalStatusHistory;
use App\Modules\Inventory\Actions\AdjustStockAction;
use App\Modules\Inventory\DTOs\AdjustStockDTO;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CancelConditionalAction
{
    public function __construct(
        private AdjustStockAction $adjustStock,
    ) {}

    public function execute(Conditional $conditional): Conditional
    {
        if (! $conditional->status->canCancel()) {
            throw new BusinessException(
                "Condicional não pode ser cancelado no status '{$conditional->status->label()}'."
            );
        }

        return DB::transaction(function () use ($conditional): Conditional {
            $conditional->load('items');
            $previousStatus = $conditional->status;

            foreach ($conditional->items as $item) {
                $pendingQty = $item->pendingQuantity();

                if ($pendingQty <= 0) {
                    continue;
                }

                // Return remaining items to inventory
                $this->adjustStock->execute(new AdjustStockDTO(
                    storeId:       $conditional->store_id,
                    variantId:     $item->variant_id,
                    quantity:      abs($pendingQty),
                    type:          MovementTypeEnum::ConditionalReturn,
                    notes:         'Cancelamento do condicional',
                    referenceType: 'conditional',
                    referenceId:   $conditional->uuid,
                    metadata:      null,
                ));

                // Mark all pending as returned
                $item->update(['returned_quantity' => $item->quantity - $item->sold_quantity]);
            }

            // Update status to Cancelled
            $conditional->update(['status' => ConditionalStatusEnum::Cancelled]);

            ConditionalStatusHistory::create([
                'conditional_id'  => $conditional->uuid,
                'previous_status' => $previousStatus->value,
                'current_status'  => ConditionalStatusEnum::Cancelled->value,
                'changed_by'      => Auth::id(),
                'changed_at'      => now(),
            ]);

            ConditionalCancelled::dispatch($conditional->tenant_id, $conditional->uuid, Auth::id());

            return $conditional->fresh()->load(['items', 'statusHistory']);
        });
    }
}
