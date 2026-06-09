<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Inventory\Actions\ReleaseStockAction;
use App\Modules\Inventory\Actions\ReserveStockAction;
use App\Modules\Inventory\DTOs\ReleaseStockDTO;
use App\Modules\Inventory\DTOs\ReserveStockDTO;
use App\Modules\Inventory\Http\Resources\StockReservationResource;
use App\Modules\Inventory\Models\StockReservation;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class StockReservationController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $reservations = StockReservation::where('tenant_id', $tenantId)
            ->when($request->string('store_id')->value(),    fn ($q, $v) => $q->where('store_id', $v))
            ->when($request->string('variant_id')->value(),  fn ($q, $v) => $q->where('variant_id', $v))
            ->when($request->string('source_type')->value(), fn ($q, $v) => $q->where('reference_type', $v))
            ->latest()
            ->get();

        return $this->success(StockReservationResource::collection($reservations));
    }

    public function store(Request $request, ReserveStockAction $action): JsonResponse
    {
        $validated = $request->validate([
            'store_id'    => ['required', 'uuid', 'exists:stores,uuid'],
            'variant_id'  => ['required', 'uuid', 'exists:catalog_variants,uuid'],
            'quantity'    => ['required', 'integer', 'min:1'],
            'source_type' => ['required', 'in:order,conditional,manual'],
            'source_uuid' => ['required', 'uuid'],
        ]);

        $action->execute(new ReserveStockDTO(
            storeId:       $validated['store_id'],
            variantId:     $validated['variant_id'],
            quantity:      $validated['quantity'],
            referenceType: $validated['source_type'],
            referenceId:   $validated['source_uuid'],
        ));

        return $this->created(null, 'Reserva criada com sucesso.');
    }

    public function destroy(StockReservation $reservation, ReleaseStockAction $action): JsonResponse
    {
        $action->execute(new ReleaseStockDTO(
            storeId:       $reservation->store_id,
            variantId:     $reservation->variant_id,
            quantity:      $reservation->quantity,
            referenceType: $reservation->reference_type,
            referenceId:   $reservation->reference_id,
        ));

        return $this->noContent();
    }
}
