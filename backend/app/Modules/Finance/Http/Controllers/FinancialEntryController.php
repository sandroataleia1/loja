<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Modules\Finance\Actions\CancelFinancialEntryAction;
use App\Modules\Finance\Actions\CreateFinancialEntryAction;
use App\Modules\Finance\Actions\PayFinancialEntryAction;
use App\Modules\Finance\DTOs\CreateFinancialEntryDTO;
use App\Modules\Finance\DTOs\PayFinancialEntryDTO;
use App\Modules\Finance\Http\Requests\CreateFinancialEntryRequest;
use App\Modules\Finance\Http\Requests\PayFinancialEntryRequest;
use App\Modules\Finance\Http\Resources\FinancialEntryResource;
use App\Modules\Finance\Models\FinancialEntry;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class FinancialEntryController extends Controller
{
    use HasApiResponse;
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FinancialEntry::class);

        $entries = FinancialEntry::query()
            ->with(['account', 'category', 'installments'])
            ->when($request->string('type')->value(), fn ($q, $v) => $q->where('type', $v))
            ->when($request->string('status')->value(), fn ($q, $v) => $q->where('status', $v))
            ->when($request->string('store_id')->value(), fn ($q, $v) => $q->where('store_id', $v))
            ->when($request->string('category_id')->value(), fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->string('account_id')->value(), fn ($q, $v) => $q->where('financial_account_id', $v))
            ->when($request->string('due_from')->value(), fn ($q, $v) => $q->where('due_date', '>=', $v))
            ->when($request->string('due_to')->value(), fn ($q, $v) => $q->where('due_date', '<=', $v))
            ->orderByDesc('due_date')
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: FinancialEntryResource::collection($entries),
            meta: [
                'current_page' => $entries->currentPage(),
                'per_page'     => $entries->perPage(),
                'total'        => $entries->total(),
                'last_page'    => $entries->lastPage(),
            ],
        );
    }

    public function store(CreateFinancialEntryRequest $request, CreateFinancialEntryAction $action): JsonResponse
    {
        $this->authorize('create', FinancialEntry::class);

        $entry = $action->execute(CreateFinancialEntryDTO::fromRequest($request));

        return $this->created(new FinancialEntryResource($entry));
    }

    public function show(FinancialEntry $financialEntry): JsonResponse
    {
        $financialEntry->load(['account', 'category', 'installments']);

        return $this->success(new FinancialEntryResource($financialEntry));
    }

    public function pay(PayFinancialEntryRequest $request, FinancialEntry $financialEntry, PayFinancialEntryAction $action): JsonResponse
    {
        $this->authorize('pay', $financialEntry);

        $entry = $action->execute($financialEntry, PayFinancialEntryDTO::fromRequest($request));

        return $this->success(new FinancialEntryResource($entry));
    }

    public function cancel(Request $request, FinancialEntry $financialEntry, CancelFinancialEntryAction $action): JsonResponse
    {
        $this->authorize('cancel', $financialEntry);

        $entry = $action->execute($financialEntry, $request->string('reason')->value() ?: null);

        return $this->success(new FinancialEntryResource($entry));
    }
}
