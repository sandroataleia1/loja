<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Actions;

use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Purchasing\Enums\PurchaseOrderStatusEnum;
use App\Modules\Purchasing\Enums\PurchaseReceiptStatusEnum;
use App\Modules\Purchasing\Events\PurchaseReceived;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\PurchaseReceipt;
use App\Modules\Purchasing\Models\PurchaseReceiptItem;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;

/**
 * Registra recebimento físico de mercadoria (total ou parcial).
 *
 * Para cada item recebido:
 *  - Valida que quantidade não excede o saldo pendente
 *  - Incrementa received_quantity no PurchaseOrderItem
 *  - Gera InventoryMovement tipo IN (entrada de estoque)
 *
 * Recalcula status do PurchaseOrder ao final:
 *  - Se todos os itens estão fully received → RECEIVED
 *  - Se pelo menos um está parcialmente recebido → PARTIALLY_RECEIVED
 */
final readonly class ReceivePurchaseAction
{
    public function __construct(
        private InventoryService $inventory,
    ) {}

    /**
     * @param array<int, array{order_item_uuid: string, quantity_received: int}> $items
     */
    public function execute(PurchaseOrder $order, array $items, ?string $notes = null): PurchaseReceipt
    {
        if (! $order->status->canReceive()) {
            throw new BusinessException(
                "Pedido {$order->code} com status '{$order->status->label()}' não pode receber mercadoria."
            );
        }

        return DB::transaction(function () use ($order, $items, $notes): PurchaseReceipt {
            $receipt = PurchaseReceipt::create([
                'purchase_order_id' => $order->uuid,
                'received_by'       => auth()->id(),
                'status'            => PurchaseReceiptStatusEnum::Completed,
                'received_at'       => now(),
                'notes'             => $notes,
            ]);

            foreach ($items as $itemData) {
                $orderItem = $order->items()
                    ->where('uuid', $itemData['order_item_uuid'])
                    ->firstOrFail();

                $qty = (int) $itemData['quantity_received'];

                if ($qty <= 0) {
                    continue;
                }

                if ($qty > $orderItem->pendingQuantity()) {
                    throw new BusinessException(
                        "Quantidade {$qty} excede o saldo pendente ({$orderItem->pendingQuantity()}) para a variante."
                    );
                }

                // Create receipt item
                PurchaseReceiptItem::create([
                    'receipt_id'              => $receipt->uuid,
                    'purchase_order_item_id'  => $orderItem->uuid,
                    'product_variant_id'      => $orderItem->product_variant_id,
                    'quantity_received'        => $qty,
                    'unit_cost'               => $orderItem->unit_cost,
                ]);

                // Increment received on order item
                $orderItem->increment('received_quantity', $qty);

                // Generate IN inventory movement
                $this->inventory->receive(
                    storeId:       $order->store_id,
                    variantId:     $orderItem->product_variant_id,
                    quantity:      $qty,
                    referenceType: 'purchase_receipt',
                    referenceId:   $receipt->uuid,
                    notes:         "Recebimento pedido {$order->code}",
                );
            }

            // Recalculate order status
            $order->refresh();
            $totalQty    = $order->items->sum('quantity');
            $receivedQty = $order->items->sum('received_quantity');

            $newStatus = match (true) {
                $receivedQty === 0              => $order->status, // no change
                $receivedQty >= $totalQty       => PurchaseOrderStatusEnum::Received,
                default                         => PurchaseOrderStatusEnum::PartiallyReceived,
            };

            $order->update(['status' => $newStatus]);

            // Compra recebida → gera contas a pagar (listener no módulo Finance).
            PurchaseReceived::dispatch($receipt);

            return $receipt->load(['items.variant', 'receivedBy']);
        });
    }
}
