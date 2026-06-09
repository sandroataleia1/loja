<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Modules\Sales\DTOs\ApplyDiscountDTO;
use App\Modules\Sales\Enums\DiscountTypeEnum;
use App\Modules\Sales\Events\DiscountApplied;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleDiscount;
use App\Modules\Sales\Models\SaleItem;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;

final readonly class ApplyDiscountAction
{
    /**
     * Aplica um desconto na venda ou em um item específico.
     *
     * Desconto percentual: calcula amount_cents sobre o base (item subtotal ou venda subtotal).
     * Desconto fixo: usa amount_cents diretamente.
     * Recalcula totais após aplicação.
     */
    public function execute(Sale $sale, ApplyDiscountDTO $dto): Sale
    {
        if (! $sale->status->isEditable()) {
            throw new BusinessException(
                "Não é possível aplicar desconto a uma venda no status '{$sale->status->label()}'."
            );
        }

        return DB::transaction(function () use ($sale, $dto): Sale {
            $item       = null;
            $amountCents = $dto->amountCents;

            if ($dto->saleItemId !== null) {
                $item = SaleItem::where('sale_id', $sale->uuid)
                    ->where('uuid', $dto->saleItemId)
                    ->firstOrFail();

                if ($dto->type === DiscountTypeEnum::Percentage) {
                    $amountCents = (int) round($item->subtotal_cents * $dto->percentage / 100);
                }

                $newItemDiscount = $item->discount_amount_cents + $amountCents;
                if ($newItemDiscount > $item->subtotal_cents) {
                    throw new BusinessException('O desconto não pode ser maior que o subtotal do item.');
                }

                $item->update([
                    'discount_amount_cents' => $newItemDiscount,
                    'total_cents'           => $item->subtotal_cents - $newItemDiscount,
                ]);
            } else {
                // Desconto na venda inteira
                if ($dto->type === DiscountTypeEnum::Percentage) {
                    $amountCents = (int) round($sale->subtotal_cents * $dto->percentage / 100);
                }

                $newSaleDiscount = $sale->discount_total_cents + $amountCents;
                if ($newSaleDiscount > $sale->subtotal_cents) {
                    throw new BusinessException('O desconto não pode ser maior que o subtotal da venda.');
                }

                $sale->update(['discount_total_cents' => $newSaleDiscount]);
            }

            $discount = SaleDiscount::create([
                'sale_id'      => $sale->uuid,
                'sale_item_id' => $dto->saleItemId,
                'type'         => $dto->type,
                'percentage'   => $dto->type === DiscountTypeEnum::Percentage ? $dto->percentage : null,
                'amount_cents' => $amountCents,
                'reason'       => $dto->reason,
                'approved_by'  => $dto->approvedBy,
            ]);

            // Recalcula total da venda com base nos itens e descontos de venda
            $sale->refresh();
            $sale->update([
                'subtotal_cents' => $sale->items()->sum('total_cents'),
                'total_cents'    => max(0,
                    $sale->items()->sum('total_cents') - $sale->discount_total_cents
                ),
            ]);

            DiscountApplied::dispatch($sale->fresh(), $discount);

            return $sale->fresh()->load(['items', 'payments', 'discounts']);
        });
    }
}
