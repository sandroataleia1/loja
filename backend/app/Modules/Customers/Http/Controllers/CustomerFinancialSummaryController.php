<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Services\CustomerFinancialSummaryService;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

final class CustomerFinancialSummaryController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function __construct(
        private readonly CustomerFinancialSummaryService $summaryService,
    ) {}

    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $summary = $this->summaryService->getSummary($customer);

        return $this->success($summary->toArray());
    }
}
