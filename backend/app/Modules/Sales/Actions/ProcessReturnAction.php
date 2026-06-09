<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Finance\Actions\CancelFinancialEntryAction;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Inventory\Actions\AdjustStockAction;
use App\Modules\Inventory\DTOs\AdjustStockDTO;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Modules\Sales\DTOs\ProcessReturnDTO;
use App\Modules\Sales\Enums\SaleReturnStatusEnum;
use App\Modules\Sales\Events\SaleReturned;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use App\Modules\Sales\Models\SaleReturn;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Processa uma solicitação de devolução ou troca.
 *
 * Fluxo:
 * 1. Valida que a venda existe e pode ser devolvida
 * 2. Valida quantidades (não devolver mais do que foi vendido/disponível)
 * 3. Cria SaleReturn + SaleReturnItems
 * 4. Repõe estoque por item devolvido via AdjustStockAction
 * 5. Cancela/reverte lançamento financeiro proporcional
 * 6. Atualiza returned_quantity em cada SaleItem
 * 7. Dispara SaleReturned event
 */
final readonly class ProcessReturnAction
{
    public function __construct(
        private AdjustStockAction         $adjustStock,
        private CancelFinancialEntryAction $cancelFinancial,
        private GenerateInternalCodeAction $generateCode,
    ) {}

    public function execute(ProcessReturnDTO $dto): SaleReturn
    {
        $tenantId = TenantContext::getIdOrFail();

        $sale = Sale::where('uuid', $dto->originalSaleId)->first();

        if ($sale === null) {
            throw new BusinessException('Venda não encontrada.');
        }

        if (!in_array($sale->status->value, ['completed', 'partially_returned'], true)) {
            throw new BusinessException(
                "Devoluções só são permitidas em vendas completadas. Status atual: {$sale->status->label()}"
            );
        }

        return DB::transaction(function () use ($dto, $sale, $tenantId): SaleReturn {
            $code = $this->generateCode->execute($tenantId, SequenceEntityEnum::Sale, 'return');

            $saleReturn = SaleReturn::create([
                'tenant_id'        => $tenantId,
                'store_id'         => $sale->store_id,
                'original_sale_id' => $sale->uuid,
                'customer_id'      => $sale->customer_id,
                'processed_by'     => Auth::id(),
                'code'             => 'DEV' . substr($code, 3), // DEV000001
                'status'           => SaleReturnStatusEnum::Processed,
                'return_type'      => $dto->returnType,
                'reason'           => $dto->reason,
                'notes'            => $dto->notes,
                'refund_method'    => $dto->refundMethod,
                'stock_restocked'  => false,
                'financial_reversed' => false,
                'processed_at'     => now(),
            ]);

            $totalRefundCents = 0;

            foreach ($dto->items as $itemDto) {
                $saleItem = SaleItem::where('uuid', $itemDto->saleItemId)
                    ->where('sale_id', $sale->uuid)
                    ->first();

                if ($saleItem === null) {
                    throw new BusinessException("Item {$itemDto->saleItemId} não pertence a esta venda.");
                }

                $maxReturnable = $saleItem->quantity - $saleItem->returned_quantity;
                if ($itemDto->quantityReturned > $maxReturnable) {
                    throw new BusinessException(
                        "Item '{$saleItem->name_snapshot}': máximo devolvível é {$maxReturnable} unidade(s)."
                    );
                }

                $refundForItem = (int) round(
                    $saleItem->unit_price_cents * $itemDto->quantityReturned
                );

                $saleReturn->items()->create([
                    'sale_item_id'       => $saleItem->uuid,
                    'variant_id'         => $saleItem->product_variant_id,
                    'quantity_returned'  => $itemDto->quantityReturned,
                    'unit_price_cents'   => $saleItem->unit_price_cents,
                    'refund_amount_cents' => $refundForItem,
                    'condition'          => $itemDto->condition,
                    'condition_notes'    => $itemDto->conditionNotes,
                ]);

                // Atualizar returned_quantity no SaleItem
                $saleItem->increment('returned_quantity', $itemDto->quantityReturned);

                // Repor estoque (apenas se item tem variante e condição permite)
                if ($saleItem->product_variant_id !== null && $itemDto->condition !== 'damaged') {
                    $this->adjustStock->execute(new AdjustStockDTO(
                        storeId:       $sale->store_id,
                        variantId:     $saleItem->product_variant_id,
                        quantity:      $itemDto->quantityReturned,
                        type:          MovementTypeEnum::Return,
                        notes:         "Devolução {$saleReturn->code}",
                        referenceType: 'sale_return',
                        referenceId:   $saleReturn->uuid,
                        metadata:      null,
                    ));
                }

                $totalRefundCents += $refundForItem;
            }

            $saleReturn->update([
                'refund_amount_cents' => $totalRefundCents,
                'stock_restocked'     => true,
            ]);

            // Reverter entrada financeira proporcional
            $entry = FinancialEntry::where('reference_type', 'sale')
                ->where('reference_id', $sale->uuid)
                ->where('status', '!=', 'cancelled')
                ->first();

            if ($entry !== null && $totalRefundCents > 0) {
                $this->cancelFinancial->execute($entry, "Devolução parcial — {$saleReturn->code}");
                $saleReturn->update(['financial_reversed' => true]);
            }

            SaleReturned::dispatch($saleReturn, $sale);

            return $saleReturn->load(['items', 'originalSale']);
        });
    }
}
