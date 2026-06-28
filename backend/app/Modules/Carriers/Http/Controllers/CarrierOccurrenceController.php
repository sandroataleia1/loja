<?php

declare(strict_types=1);

namespace App\Modules\Carriers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Carriers\Models\Carrier;
use App\Modules\Carriers\Models\CarrierOccurrence;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CarrierOccurrenceController extends Controller
{
    use HasApiResponse;

    public function index(Carrier $carrier): JsonResponse
    {
        return $this->success(
            $carrier->occurrences()->with('registeredBy:uuid,name')->paginate(20),
        );
    }

    public function store(Request $request, Carrier $carrier): JsonResponse
    {
        $validated = $request->validate([
            'occurrence_type' => ['required', 'string', 'in:' . implode(',', CarrierOccurrence::TYPES)],
            'tracking_code'   => ['nullable', 'string', 'max:80'],
            'order_reference' => ['nullable', 'string', 'max:80'],
            'description'     => ['nullable', 'string'],
            'occurred_at'     => ['nullable', 'date'],
        ]);

        $occurrence = CarrierOccurrence::create(array_merge(
            [
                'tenant_id'     => $carrier->tenant_id,
                'carrier_id'    => $carrier->uuid,
                'registered_by' => $request->user()->uuid,
                'occurred_at'   => $validated['occurred_at'] ?? now(),
            ],
            $validated,
        ));

        return $this->created($occurrence);
    }

    public function destroy(Carrier $carrier, CarrierOccurrence $occurrence): JsonResponse
    {
        $occurrence->delete();

        return $this->noContent();
    }
}
