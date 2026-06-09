<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Modules\Finance\Actions\CreateFinancialAccountAction;
use App\Modules\Finance\DTOs\CreateFinancialAccountDTO;
use App\Modules\Finance\Http\Requests\CreateFinancialAccountRequest;
use App\Modules\Finance\Http\Resources\FinancialAccountResource;
use App\Modules\Finance\Models\FinancialAccount;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class FinancialAccountController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $accounts = FinancialAccount::query()
            ->when($request->string('store_id')->value(), fn ($q, $v) => $q->where('store_id', $v))
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->when($request->string('type')->value(), fn ($q, $v) => $q->where('type', $v))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: FinancialAccountResource::collection($accounts),
            meta: [
                'current_page' => $accounts->currentPage(),
                'per_page'     => $accounts->perPage(),
                'total'        => $accounts->total(),
                'last_page'    => $accounts->lastPage(),
            ],
        );
    }

    public function store(CreateFinancialAccountRequest $request, CreateFinancialAccountAction $action): JsonResponse
    {
        $this->authorize('create', FinancialAccount::class);

        $account = $action->execute(CreateFinancialAccountDTO::fromRequest($request));

        return $this->created(new FinancialAccountResource($account));
    }

    public function show(FinancialAccount $financialAccount): JsonResponse
    {
        return $this->success(new FinancialAccountResource($financialAccount));
    }

    public function update(CreateFinancialAccountRequest $request, FinancialAccount $financialAccount): JsonResponse
    {
        $this->authorize('update', $financialAccount);

        if ($request->filled('code') && $request->string('code')->value() !== $financialAccount->code) {
            if (FinancialAccount::where('code', $request->string('code')->value())->exists()) {
                throw new ConflictException("Código '{$request->string('code')}' já está em uso.");
            }
        }

        $financialAccount->update($request->only(['code', 'name', 'type', 'is_active']));

        return $this->success(new FinancialAccountResource($financialAccount->refresh()));
    }

    public function destroy(FinancialAccount $financialAccount): JsonResponse
    {
        $this->authorize('delete', $financialAccount);

        $financialAccount->delete();

        return $this->noContent();
    }
}
