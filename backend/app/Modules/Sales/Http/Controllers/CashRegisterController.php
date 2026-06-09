<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Controllers;

use App\Modules\Sales\DTOs\CreateCashRegisterDTO;
use App\Modules\Sales\Http\Requests\CreateCashRegisterRequest;
use App\Modules\Sales\Http\Resources\CashRegisterResource;
use App\Modules\Sales\Models\CashRegister;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class CashRegisterController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $registers = CashRegister::query()
            ->with(['store', 'openSession'])
            ->when($request->string('store_id')->value(), fn ($q, $v) => $q->where('store_id', $v))
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: CashRegisterResource::collection($registers),
            meta: [
                'current_page' => $registers->currentPage(),
                'per_page'     => $registers->perPage(),
                'total'        => $registers->total(),
                'last_page'    => $registers->lastPage(),
            ],
        );
    }

    public function store(CreateCashRegisterRequest $request): JsonResponse
    {
        $this->authorize('create', CashRegister::class);

        $dto = CreateCashRegisterDTO::fromRequest($request);

        if (CashRegister::where('store_id', $dto->storeId)->where('code', $dto->code)->exists()) {
            throw new ConflictException("Código '{$dto->code}' já está em uso nesta loja.");
        }

        $register = CashRegister::create([
            'store_id'  => $dto->storeId,
            'code'      => $dto->code,
            'name'      => $dto->name,
            'is_active' => $dto->isActive,
        ]);

        return $this->created(new CashRegisterResource($register->load('store')));
    }

    public function show(CashRegister $cashRegister): JsonResponse
    {
        $cashRegister->load(['store', 'openSession']);

        return $this->success(new CashRegisterResource($cashRegister));
    }

    public function update(CreateCashRegisterRequest $request, CashRegister $cashRegister): JsonResponse
    {
        $this->authorize('update', $cashRegister);

        $cashRegister->update($request->only(['code', 'name', 'is_active']));

        return $this->success(new CashRegisterResource($cashRegister->refresh()->load('store')));
    }

    public function destroy(CashRegister $cashRegister): JsonResponse
    {
        $this->authorize('delete', $cashRegister);

        if ($cashRegister->hasOpenSession()) {
            throw new ConflictException('Não é possível excluir um caixa com sessão aberta.');
        }

        $cashRegister->delete();

        return $this->noContent();
    }
}
