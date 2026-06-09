<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Inventory\Actions\AdjustStockAction;
use App\Modules\Inventory\DTOs\AdjustStockDTO;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Modules\Inventory\Http\Resources\InventoryAdjustmentResource;
use App\Modules\Inventory\Models\InventoryAdjustment;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class InventoryAdjustmentController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $adjustments = InventoryAdjustment::where('tenant_id', $tenantId)
            ->when($request->string('store_id')->value(),   fn ($q, $v) => $q->where('store_id', $v))
            ->when($request->string('variant_id')->value(), fn ($q, $v) => $q->where('variant_id', $v))
            ->when($request->string('date_from')->value(),  fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->string('date_to')->value(),    fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->with(['store', 'variant'])
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: InventoryAdjustmentResource::collection($adjustments),
            meta: [
                'current_page' => $adjustments->currentPage(),
                'per_page'     => $adjustments->perPage(),
                'total'        => $adjustments->total(),
                'last_page'    => $adjustments->lastPage(),
            ],
        );
    }

    public function store(Request $request, AdjustStockAction $action): JsonResponse
    {
        $validated = $request->validate([
            'store_id'   => ['required', 'uuid', 'exists:stores,uuid'],
            'variant_id' => ['required', 'uuid', 'exists:catalog_variants,uuid'],
            'quantity'   => ['required', 'integer', 'not_in:0'],
            'reason'     => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $action->execute(new AdjustStockDTO(
            storeId:       $validated['store_id'],
            variantId:     $validated['variant_id'],
            quantity:      $validated['quantity'],
            type:          MovementTypeEnum::Adjustment,
            notes:         $validated['reason'],
            referenceType: 'manual_adjustment',
            referenceId:   null,
            metadata:      null,
        ));

        $adjustment = InventoryAdjustment::where('store_id', $validated['store_id'])
            ->where('variant_id', $validated['variant_id'])
            ->with(['store', 'variant'])
            ->latest()
            ->firstOrFail();

        return $this->created(new InventoryAdjustmentResource($adjustment));
    }
}
