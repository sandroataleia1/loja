<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Purchasing\Actions\CreatePurchaseOrderAction;
use App\Modules\Purchasing\Actions\ReceivePurchaseAction;
use App\Modules\Purchasing\Enums\PurchaseOrderStatusEnum;
use App\Modules\Purchasing\Http\Resources\PurchaseOrderResource;
use App\Modules\Purchasing\Http\Resources\PurchaseReceiptResource;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class PurchaseOrderController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $orders = PurchaseOrder::where('tenant_id', $tenantId)
            ->with(['supplier'])
            ->when($request->filled('status'),      fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('store_id'),    fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('q'),           fn ($q) => $q->where('code', 'ilike', "%{$request->q}%"))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: PurchaseOrderResource::collection($orders),
            meta: [
                'current_page' => $orders->currentPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
                'last_page'    => $orders->lastPage(),
            ],
        );
    }

    public function store(Request $request, CreatePurchaseOrderAction $action): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id'              => ['required', 'uuid', 'exists:suppliers,uuid'],
            'store_id'                 => ['required', 'uuid', 'exists:stores,uuid'],
            'order_date'               => ['required', 'date'],
            'expected_delivery_date'   => ['nullable', 'date', 'after_or_equal:order_date'],
            'discount'                 => ['nullable', 'numeric', 'min:0'],
            'notes'                    => ['nullable', 'string'],
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'uuid', 'exists:catalog_variants,uuid'],
            'items.*.quantity'         => ['required', 'integer', 'min:1'],
            'items.*.unit_cost'        => ['required', 'numeric', 'min:0'],
        ]);

        $order = $action->execute($validated);

        return $this->created(new PurchaseOrderResource($order));
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        return $this->success(
            new PurchaseOrderResource($purchaseOrder->load(['supplier', 'items.variant', 'receipts']))
        );
    }

    public function send(PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->status !== PurchaseOrderStatusEnum::Draft) {
            return $this->error('Somente pedidos em rascunho podem ser enviados.', status: 422);
        }

        $purchaseOrder->update(['status' => PurchaseOrderStatusEnum::Sent]);

        return $this->success(new PurchaseOrderResource($purchaseOrder->refresh()->load(['supplier', 'items'])));
    }

    public function cancel(PurchaseOrder $purchaseOrder): JsonResponse
    {
        if (! $purchaseOrder->status->canCancel()) {
            return $this->error("Pedido com status '{$purchaseOrder->status->label()}' não pode ser cancelado.", status: 422);
        }

        $purchaseOrder->update(['status' => PurchaseOrderStatusEnum::Cancelled]);

        return $this->success(new PurchaseOrderResource($purchaseOrder->refresh()));
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder, ReceivePurchaseAction $action): JsonResponse
    {
        $validated = $request->validate([
            'items'                          => ['required', 'array', 'min:1'],
            'items.*.order_item_uuid'        => ['required', 'uuid'],
            'items.*.quantity_received'      => ['required', 'integer', 'min:1'],
            'notes'                          => ['nullable', 'string'],
        ]);

        $receipt = $action->execute($purchaseOrder, $validated['items'], $validated['notes'] ?? null);

        return $this->created(new PurchaseReceiptResource($receipt));
    }
}
