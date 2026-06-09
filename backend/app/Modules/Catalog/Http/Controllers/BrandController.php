<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Actions\CreateBrandAction;
use App\Modules\Catalog\Actions\UpdateBrandAction;
use App\Modules\Catalog\DTOs\CreateBrandDTO;
use App\Modules\Catalog\DTOs\UpdateBrandDTO;
use App\Modules\Catalog\Http\Requests\StoreBrandRequest;
use App\Modules\Catalog\Http\Requests\UpdateBrandRequest;
use App\Modules\Catalog\Http\Resources\BrandResource;
use App\Modules\Catalog\Models\Brand;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

final class BrandController extends Controller
{
    use HasApiResponse;

    public function index(): JsonResponse
    {
        $brands = Brand::where('is_active', true)->orderBy('name')->paginate(50);

        return $this->success(
            data: BrandResource::collection($brands),
            meta: [
                'current_page' => $brands->currentPage(),
                'per_page'     => $brands->perPage(),
                'total'        => $brands->total(),
                'last_page'    => $brands->lastPage(),
            ],
        );
    }

    public function store(StoreBrandRequest $request, CreateBrandAction $action): JsonResponse
    {
        $brand = $action->execute(CreateBrandDTO::fromRequest($request));

        return $this->created(new BrandResource($brand));
    }

    public function show(Brand $brand): JsonResponse
    {
        return $this->success(new BrandResource($brand));
    }

    public function update(UpdateBrandRequest $request, Brand $brand, UpdateBrandAction $action): JsonResponse
    {
        $brand = $action->execute($brand, UpdateBrandDTO::fromRequest($request));

        return $this->success(new BrandResource($brand));
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $brand->delete();

        return $this->noContent();
    }
}
