<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Actions\CreateVariantAction;
use App\Modules\Catalog\Actions\GenerateVariantsAction;
use App\Modules\Catalog\Actions\UpdateVariantAction;
use App\Modules\Catalog\DTOs\CreateVariantDTO;
use App\Modules\Catalog\DTOs\GenerateVariantsDTO;
use App\Modules\Catalog\DTOs\UpdateVariantDTO;
use App\Modules\Catalog\Http\Requests\GenerateVariantsRequest;
use App\Modules\Catalog\Http\Requests\StoreVariantRequest;
use App\Modules\Catalog\Http\Requests\UpdateVariantRequest;
use App\Modules\Catalog\Http\Resources\VariantResource;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Variant;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

final class VariantController extends Controller
{
    use HasApiResponse;

    public function index(Product $product): JsonResponse
    {
        $variants = $product->variants()->with(['attributes', 'images'])->get();

        return $this->success(VariantResource::collection($variants));
    }

    public function store(StoreVariantRequest $request, CreateVariantAction $action): JsonResponse
    {
        $variant = $action->execute(CreateVariantDTO::fromRequest($request));

        return $this->created(new VariantResource($variant));
    }

    public function show(Product $product, Variant $variant): JsonResponse
    {
        $variant->load(['attributes', 'images']);

        return $this->success(new VariantResource($variant));
    }

    public function update(
        UpdateVariantRequest $request,
        Product $product,
        Variant $variant,
        UpdateVariantAction $action,
    ): JsonResponse {
        $variant = $action->execute($variant, UpdateVariantDTO::fromRequest($request));

        return $this->success(new VariantResource($variant));
    }

    public function destroy(Product $product, Variant $variant): JsonResponse
    {
        $variant->delete();

        return $this->noContent();
    }

    public function generate(GenerateVariantsRequest $request, GenerateVariantsAction $action): JsonResponse
    {
        $variants = $action->execute(GenerateVariantsDTO::fromRequest($request));

        return $this->created(
            data: VariantResource::collection(collect($variants)),
            message: count($variants) . ' variante(s) gerada(s) com sucesso.',
        );
    }
}
