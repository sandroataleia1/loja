<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers;

use App\Modules\Inventory\Http\Requests\StoreStoreRequest;
use App\Modules\Inventory\Http\Requests\UpdateStoreRequest;
use App\Modules\Inventory\Http\Resources\StoreResource;
use App\Modules\Inventory\Models\Store;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class StoreController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $stores = Store::query()
            ->when($request->boolean('active'), fn ($q) => $q->where('is_active', true))
            ->when($request->string('q')->value(), fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: StoreResource::collection($stores),
            meta: [
                'current_page' => $stores->currentPage(),
                'per_page'     => $stores->perPage(),
                'total'        => $stores->total(),
                'last_page'    => $stores->lastPage(),
            ],
        );
    }

    public function store(StoreStoreRequest $request): JsonResponse
    {
        if (Store::where('code', $request->string('code')->toString())->exists()) {
            throw new ConflictException("Código de loja '{$request->string('code')}' já existe nesta empresa.");
        }

        $store = Store::create($request->validated());

        return $this->created(new StoreResource($store));
    }

    public function show(Store $store): JsonResponse
    {
        return $this->success(new StoreResource($store));
    }

    public function update(UpdateStoreRequest $request, Store $store): JsonResponse
    {
        $store->update($request->validated());

        return $this->success(new StoreResource($store->fresh()));
    }

    public function destroy(Store $store): JsonResponse
    {
        $store->delete();

        return $this->noContent();
    }
}
