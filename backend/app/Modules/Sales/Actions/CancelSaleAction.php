<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Modules\Inventory\Actions\AdjustStockAction;
use App\Modules\Inventory\DTOs\AdjustStockDTO;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Modules\Sales\DTOs\CancelSaleDTO;
use App\Modules\Sales\Enums\PaymentStatusEnum;
use App\Modules\Sales\Enums\SaleStatusEnum;
use App\Modules\Sales\Events\SaleCancelled;
use App\Modules\Sales\Models\Sale;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;

final readonly class CancelSaleAction
{
    public function __construct(
        private AdjustStockAction $adjustStock,
    ) {}

    /**
     * Cancela uma venda. Nunca deleta — apenas muda status para CANCELLED.
     *
     * Se a venda estava COMPLETED:
     *   - Reverte movimentos de estoque (entrada positiva = devolução).
     *   - Marca pagamentos PAID como REFUNDED.
     *
     * Vendas já CANCELLED ou REFUNDED não podem ser canceladas novamente.
     */
    public function execute(Sale $sale, CancelSaleDTO $dto): Sale
    {
        if (! $sale->status->isCancellable()) {
            throw new BusinessException(
                "Venda não pode ser cancelada no status '{$sale->status->label()}'."
            );
        }

        return DB::transaction(function () use ($sale, $dto): Sale {
            $sale->load(['items', 'payments']);

            // Reverte estoque apenas se a venda foi concluída (estoque foi decrementado)
            if ($sale->status->stockWasDecremented()) {
                foreach ($sale->items as $item) {
                    if ($item->product_variant_id === null) {
                        continue;
                    }

                    $this->adjustStock->execute(new AdjustStockDTO(
                        storeId:       $sale->store_id,
                        variantId:     $item->product_variant_id,
                        quantity:      $item->quantity,
                        type:          MovementTypeEnum::Return,
                        notes:         "Cancelamento da venda #{$sale->uuid}",
                        referenceType: 'sale',
                        referenceId:   $sale->uuid,
                        metadata:      null,
                    ));
                }

                // Estorna pagamentos pagos
                $sale->payments()
                    ->where('status', PaymentStatusEnum::Paid)
                    ->update(['status' => PaymentStatusEnum::Refunded]);
            }

            $notes = $sale->notes;
            if ($dto->reason !== null) {
                $notes = "[CANCELAMENTO: {$dto->reason}] " . $notes;
            }

            $sale->update([
                'status'       => SaleStatusEnum::Cancelled,
                'cancelled_at' => now(),
                'notes'        => $notes,
            ]);

            $updated = $sale->fresh()->load(['items', 'payments', 'discounts', 'store', 'seller']);

            SaleCancelled::dispatch($updated, $dto->reason);

            return $updated;
        });
    }
}
