<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Actions\CreditAnalysisAction;
use App\Modules\Customers\Models\Customer;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CreditAnalysisController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function __construct(private readonly CreditAnalysisAction $action) {}

    public function store(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'monthly_income'  => ['nullable', 'numeric', 'min:0'],
            'profession'      => ['nullable', 'string', 'max:100'],
            'employer'        => ['nullable', 'string', 'max:150'],
            'employer_phone'  => ['nullable', 'string', 'max:20'],
            'work_start_date' => ['nullable', 'date'],
            'credit_score'    => ['nullable', 'integer', 'min:0', 'max:10'],
            'credit_notes'    => ['nullable', 'string'],
            'spc_status'      => ['nullable', 'string', 'in:clean,restricted,unknown'],
        ]);

        $updated = $this->action->execute($customer, $validated, $request->user());

        return $this->success([
            'credit' => [
                'credit_score'         => $updated->credit_score,
                'credit_score_label'   => $updated->creditScoreLabel(),
                'spc_status'           => $updated->spc_status?->value,
                'spc_status_label'     => $updated->spc_status?->label(),
                'credit_analysis_date' => $updated->credit_analysis_date?->toDateString(),
                'has_income'           => $updated->hasIncome(),
            ],
        ]);
    }
}
