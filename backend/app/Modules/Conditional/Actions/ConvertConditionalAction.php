<?php

declare(strict_types=1);

namespace App\Modules\Conditional\Actions;

use App\Modules\Conditional\Enums\ConditionalStatusEnum;
use App\Modules\Conditional\Events\ConditionalConverted;
use App\Modules\Conditional\Events\ConditionalPartiallyConverted;
use App\Modules\Conditional\Models\Conditional;
use App\Modules\Conditional\Models\ConditionalItem;
use App\Modules\Conditional\Models\ConditionalStatusHistory;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class ConvertConditionalAction
{
    /**
     * @param  array<int, array{item_uuid: string, quantity: int}>  $conversions
     */
    public function execute(Conditional $conditional, array $conversions): Conditional
    {
        if (! $conditional->status->canConvert()) {
            throw new BusinessException(
                "Condicional não pode ser convertido no status '{$conditional->status->label()}'."
            );
        }

        return DB::transaction(function () use ($conditional, $conversions): Conditional {
            $conditional->load('items');

            foreach ($conversions as $conversion) {
                /** @var ConditionalItem|null $item */
                $item = $conditional->items->firstWhere('uuid', $conversion['item_uuid']);

                if ($item === null) {
                    throw new BusinessException("Item '{$conversion['item_uuid']}' não pertence a este condicional.");
                }

                $convertQty = (int) $conversion['quantity'];

                if ($convertQty > $item->pendingQuantity()) {
                    throw new BusinessException(
                        "Quantidade de conversão ({$convertQty}) excede o pendente ({$item->pendingQuantity()}) para o item '{$item->uuid}'."
                    );
                }

                // No inventory movement — stock already left via ConditionalOut at opening
                $item->increment('sold_quantity', $convertQty);
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

                if ($newStatus === ConditionalStatusEnum::Converted) {
                    ConditionalConverted::dispatch($conditional->tenant_id, $conditional->uuid, Auth::id());
                } else {
                    ConditionalPartiallyConverted::dispatch($conditional->tenant_id, $conditional->uuid, Auth::id());
                }
            }

            return $conditional->fresh();
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
