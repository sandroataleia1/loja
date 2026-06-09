<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Purchasing\Enums\PurchaseOrderStatusEnum;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\PurchaseOrderItem;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use Illuminate\Support\Facades\DB;

final readonly class CreatePurchaseOrderAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
    ) {}

    /** @param array{supplier_id:string, store_id:string, order_date:string, expected_delivery_date?:string, notes?:string, items:array} $data */
    public function execute(array $data): PurchaseOrder
    {
        $tenantId = TenantContext::getIdOrFail();

        return DB::transaction(function () use ($data, $tenantId): PurchaseOrder {
            $code = $this->generateCode->execute($tenantId, SequenceEntityEnum::PurchaseOrder);

            $subtotal = collect($data['items'])->sum(
                fn ($i) => $i['quantity'] * $i['unit_cost']
            );
            $discount = $data['discount'] ?? 0;

            $order = PurchaseOrder::create([
                'tenant_id'               => $tenantId,
                'store_id'                => $data['store_id'],
                'supplier_id'             => $data['supplier_id'],
                'code'                    => $code,
                'status'                  => PurchaseOrderStatusEnum::Draft,
                'order_date'              => $data['order_date'],
                'expected_delivery_date'  => $data['expected_delivery_date'] ?? null,
                'subtotal'                => $subtotal,
                'discount'                => $discount,
                'total'                   => $subtotal - $discount,
                'notes'                   => $data['notes'] ?? null,
                'created_by'              => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id'  => $order->uuid,
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity'           => $item['quantity'],
                    'unit_cost'          => $item['unit_cost'],
                ]);
            }

            return $order->load(['items.variant', 'supplier']);
        });
    }
}
