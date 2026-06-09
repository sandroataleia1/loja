<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Actions\CreateCollectionAction;
use App\Modules\Catalog\DTOs\CreateCollectionDTO;
use App\Modules\Catalog\Http\Requests\StoreCollectionRequest;
use App\Modules\Catalog\Http\Resources\CollectionResource;
use App\Modules\Catalog\Models\ProductCollection;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

final class CollectionController extends Controller
{
    use HasApiResponse;

    public function index(): JsonResponse
    {
        $collections = ProductCollection::latest()->paginate(20);

        return $this->success(
            data: CollectionResource::collection($collections),
            meta: [
                'current_page' => $collections->currentPage(),
                'per_page'     => $collections->perPage(),
                'total'        => $collections->total(),
                'last_page'    => $collections->lastPage(),
            ],
        );
    }

    public function store(StoreCollectionRequest $request, CreateCollectionAction $action): JsonResponse
    {
        $collection = $action->execute(CreateCollectionDTO::fromRequest($request));

        return $this->created(new CollectionResource($collection));
    }

    public function show(ProductCollection $collection): JsonResponse
    {
        return $this->success(new CollectionResource($collection));
    }

    public function update(StoreCollectionRequest $request, ProductCollection $collection): JsonResponse
    {
        $collection->update($request->validated());

        return $this->success(new CollectionResource($collection->refresh()));
    }

    public function destroy(ProductCollection $collection): JsonResponse
    {
        $collection->delete();

        return $this->noContent();
    }
}
