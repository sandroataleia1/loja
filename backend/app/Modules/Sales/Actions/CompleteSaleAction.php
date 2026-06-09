<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Modules\Inventory\Actions\AdjustStockAction;
use App\Modules\Inventory\DTOs\AdjustStockDTO;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Modules\Sales\Enums\CashMovementTypeEnum;
use App\Modules\Sales\Enums\SalesChannelEnum;
use App\Modules\Sales\Enums\SaleStatusEnum;
use App\Modules\Sales\Events\SaleCompleted;
use App\Modules\Sales\Models\CashMovement;
use App\Modules\Sales\Models\Sale;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CompleteSaleAction
{
    public function __construct(
        private AdjustStockAction $adjustStock,
    ) {}

    /**
     * Finaliza a venda:
     * 1. Valida que os pagamentos PAID cobrem o total.
     * 2. Debita estoque de cada item com variante conhecida.
     * 3. Registra movimentação SALE no caixa (se sessão presente).
     * 4. Marca a venda como COMPLETED.
     */
    public function execute(Sale $sale): Sale
    {
        if (! $sale->status->isEditable()) {
            throw new BusinessException(
                "Venda não pode ser concluída no status '{$sale->status->label()}'."
            );
        }

        if ($sale->items()->count() === 0) {
            throw new BusinessException('Não é possível concluir uma venda sem itens.');
        }

        if (! $sale->isFullyPaid()) {
            $remaining = $sale->remainingAmountCents();
            throw new BusinessException(
                'Pagamento insuficiente. Faltam R$ ' . number_format($remaining / 100, 2, ',', '.') . ' para cobrir o total.'
            );
        }

        // A7-02: venda de PDV exige caixa aberto (domain-rule). Outros canais
        // (ecommerce, social, marketplace) não dependem de sessão de caixa.
        if ($sale->sales_channel === SalesChannelEnum::Pdv) {
            $session = $sale->session_id !== null ? $sale->session : null;

            if ($session === null || ! $session->isOpen()) {
                throw new BusinessException(
                    'Venda de PDV exige uma sessão de caixa aberta.'
                );
            }
        }

        return DB::transaction(function () use ($sale): Sale {
            $sale->load('items');

            foreach ($sale->items as $item) {
                if ($item->product_variant_id === null) {
                    continue;
                }

                $this->adjustStock->execute(new AdjustStockDTO(
                    storeId:       $sale->store_id,
                    variantId:     $item->product_variant_id,
                    quantity:      -$item->quantity,
                    type:          MovementTypeEnum::Sale,
                    notes:         "Venda #{$sale->uuid}",
                    referenceType: 'sale',
                    referenceId:   $sale->uuid,
                    metadata:      null,
                ));
            }

            // Registra movimentação SALE no caixa se a venda pertence a uma sessão
            if ($sale->session_id !== null) {
                CashMovement::create([
                    'store_id'                 => $sale->store_id,
                    'cash_register_session_id' => $sale->session_id,
                    'type'                     => CashMovementTypeEnum::Sale,
                    'amount_cents'             => $sale->total_cents,
                    'description'              => "Venda {$sale->code}",
                    'created_by'               => Auth::id(),
                ]);
            }

            $sale->update([
                'status'       => SaleStatusEnum::Completed,
                'completed_at' => now(),
            ]);

            $updated = $sale->fresh()->load(['items', 'payments', 'discounts', 'commissions', 'store', 'seller']);

            SaleCompleted::dispatch($updated);

            return $updated;
        });
    }
}
