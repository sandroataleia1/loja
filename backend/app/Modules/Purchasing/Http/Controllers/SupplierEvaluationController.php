<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Purchasing\Models\SupplierEvaluation;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SupplierEvaluationController extends Controller
{
    use HasApiResponse;

    public function index(Supplier $supplier): JsonResponse
    {
        return $this->success($supplier->evaluations()->with('evaluatedBy:uuid,name')->get());
    }

    public function store(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'reference_date'        => ['required', 'date'],
            'delivery_score'        => ['required', 'integer', 'min:1', 'max:5'],
            'quality_score'         => ['required', 'integer', 'min:1', 'max:5'],
            'price_score'           => ['required', 'integer', 'min:1', 'max:5'],
            'service_score'         => ['required', 'integer', 'min:1', 'max:5'],
            'avg_delivery_days'     => ['nullable', 'integer', 'min:0'],
            'on_time_delivery_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'return_rate'           => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'                 => ['nullable', 'string'],
        ]);

        $evaluation = SupplierEvaluation::create(array_merge(
            [
                'tenant_id'    => $supplier->tenant_id,
                'supplier_id'  => $supplier->uuid,
                'evaluated_by' => $request->user()->uuid,
            ],
            $validated,
        ));

        $supplier->update([
            'performance_score' => $evaluation->overall_score,
            'avg_delivery_days' => $evaluation->avg_delivery_days ?? $supplier->avg_delivery_days,
            'return_rate'       => $evaluation->return_rate ?? $supplier->return_rate,
        ]);

        return $this->created($evaluation);
    }

    public function destroy(Supplier $supplier, SupplierEvaluation $evaluation): JsonResponse
    {
        $evaluation->delete();

        return $this->noContent();
    }
}
