<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Events\ProductCollectionAssigned;
use App\Modules\Catalog\Http\Resources\CollectionResource;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductCollection;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

/**
 * Gerencia associação muitos-para-muitos entre produto e coleções comerciais.
 *
 * Separado da relação editorial (product.collection_id).
 * Permite associar um produto a campanhas/sazonalidades simultâneas.
 */
final class ProductCollectionItemController extends Controller
{
    use HasApiResponse;

    public function index(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return $this->success(
            CollectionResource::collection($product->commercialCollections()->get()),
        );
    }

    public function attach(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $request->validate([
            'collection_id' => ['required', 'uuid', 'exists:catalog_collections,uuid'],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
        ]);

        $collectionId = $request->string('collection_id')->toString();

        if ($product->commercialCollections()->where('uuid', $collectionId)->exists()) {
            return $this->success(null, 'Produto já pertence a esta coleção.');
        }

        $product->commercialCollections()->attach($collectionId, [
            'sort_order' => $request->integer('sort_order', 0),
        ]);

        $collection = ProductCollection::findOrFail($collectionId);
        ProductCollectionAssigned::dispatch($product, $collection);

        return $this->success(
            CollectionResource::collection($product->commercialCollections()->get()),
            'Produto adicionado à coleção.',
        );
    }

    public function detach(Product $product, ProductCollection $collection): JsonResponse
    {
        $this->authorize('update', $product);

        $product->commercialCollections()->detach($collection->uuid);

        return $this->noContent();
    }
}
