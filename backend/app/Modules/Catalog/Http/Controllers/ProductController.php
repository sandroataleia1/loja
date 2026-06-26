<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Actions\ArchiveProductAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Actions\DuplicateProductAction;
use App\Modules\Catalog\Actions\PublishProductAction;
use App\Modules\Catalog\Actions\UpdateProductAction;
use App\Modules\Catalog\Services\QrCodeService;
use App\Modules\Catalog\DTOs\CreateProductDTO;
use App\Modules\Catalog\DTOs\UpdateProductDTO;
use App\Modules\Catalog\Enums\ProductStatusEnum;
use App\Modules\Catalog\Http\Requests\StoreProductRequest;
use App\Modules\Catalog\Http\Requests\UpdateProductRequest;
use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Models\Product;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

final class ProductController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $search   = $request->string('q')->value();
        $barcode  = $request->string('barcode')->value();
        $minPrice = $request->has('min_price') ? $request->integer('min_price') : null;
        $maxPrice = $request->has('max_price') ? $request->integer('max_price') : null;
        $attrId   = $request->string('attribute_id')->value();

        $query = Product::query()
            ->with(['brand', 'categories', 'images', 'unit'])
            ->when($request->string('status')->value(), fn ($q, $v) => $q->where('status', $v))
            ->when($request->string('type')->value(),   fn ($q, $v) => $q->where('type', $v))
            ->when($request->string('brand_id')->value(), fn ($q, $v) => $q->where('brand_id', $v))
            ->when($request->string('category_id')->value(), fn ($q, $v) => $q->whereHas('categories', fn ($q2) => $q2->where('uuid', $v)))
            ->when($request->boolean('featured'), fn ($q) => $q->where('is_featured', true))
            // Full-text search (>= 3 chars) ou LIKE para códigos curtos
            ->when($search, function ($q, $v): void {
                if (mb_strlen($v) >= 3) {
                    $q->whereRaw(
                        "search_vector @@ plainto_tsquery('portuguese', ?)",
                        [$v]
                    )->orderByRaw(
                        "ts_rank(search_vector, plainto_tsquery('portuguese', ?)) DESC",
                        [$v]
                    );
                } else {
                    $q->where('name', 'like', "%{$v}%");
                }
            })
            // Filtro por faixa de preço (centavos, usa preço base da variante padrão)
            ->when($minPrice !== null, fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('price_cents', '>=', $minPrice)))
            ->when($maxPrice !== null, fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('price_cents', '<=', $maxPrice)))
            // Filtro por atributo específico
            ->when($attrId, fn ($q, $v) => $q->whereHas('variants.attributes', fn ($a) => $a->where('uuid', $v)))
            // Filtro por código de barras (lookup exato via tabela dedicada)
            ->when($barcode, fn ($q, $v) => $q->whereHas('barcodes', fn ($b) => $b->where('value', $v)))
            ->latest();

        $products = $query->paginate($request->integer('per_page', 20));

        return $this->success(
            data: ProductResource::collection($products),
            meta: [
                'current_page' => $products->currentPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
                'last_page'    => $products->lastPage(),
            ],
        );
    }

    public function store(StoreProductRequest $request, CreateProductAction $action): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = $action->execute(CreateProductDTO::fromRequest($request));

        return $this->created(new ProductResource($product));
    }

    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $product->load(['brand', 'categories', 'collection', 'grid', 'variants.attributes', 'images', 'tags', 'barcodes']);

        return $this->success(new ProductResource($product));
    }

    public function update(UpdateProductRequest $request, Product $product, UpdateProductAction $action): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $action->execute($product, UpdateProductDTO::fromRequest($request));

        return $this->success(new ProductResource($product->load(['brand', 'categories', 'collection', 'grid', 'tags', 'barcodes'])));
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return $this->noContent();
    }

    public function publish(Product $product, PublishProductAction $action): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $action->execute($product);

        return $this->success(new ProductResource($product), 'Produto publicado com sucesso.');
    }

    public function archive(Product $product, ArchiveProductAction $action): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $action->execute($product);

        return $this->success(new ProductResource($product), 'Produto arquivado com sucesso.');
    }

    public function duplicate(Product $product, DuplicateProductAction $action): JsonResponse
    {
        $this->authorize('create', Product::class);

        $copy = $action->execute($product);

        return $this->created(new ProductResource($copy), 'Produto duplicado com sucesso.');
    }

    public function qrcode(Product $product, QrCodeService $service): Response
    {
        $this->authorize('view', $product);

        // Se já temos URL armazenada, redireciona para ela
        if ($product->qr_code_url && str_starts_with($product->qr_code_url, 'http')) {
            return response()->redirectTo($product->qr_code_url);
        }

        // Gera on-demand e retorna PNG inline
        $url = $service->generateForProduct($product);

        return redirect($url);
    }
}
