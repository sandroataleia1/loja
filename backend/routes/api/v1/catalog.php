<?php

declare(strict_types=1);

use App\Modules\Catalog\Http\Controllers\AttributeGroupController;
use App\Modules\Catalog\Http\Controllers\BarcodeController;
use App\Modules\Catalog\Http\Controllers\BrandController;
use App\Modules\Catalog\Http\Controllers\CategoryController;
use App\Modules\Catalog\Http\Controllers\CollectionController;
use App\Modules\Catalog\Http\Controllers\GridController;
use App\Modules\Catalog\Http\Controllers\PriceListController;
use App\Modules\Catalog\Http\Controllers\ProductCollectionItemController;
use App\Modules\Catalog\Http\Controllers\ProductController;
use App\Modules\Catalog\Http\Controllers\ProductImageController;
use App\Modules\Catalog\Http\Controllers\ProductImportController;
use App\Modules\Catalog\Http\Controllers\ProductPriceHistoryController;
use App\Modules\Catalog\Http\Controllers\UnitConversionController;
use App\Modules\Catalog\Http\Controllers\VariantController;
use App\Modules\Media\Http\Controllers\MediaAssetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Catalog — Read routes (open to any authenticated user)
| Product/variant search is needed by salespeople and PDV cashiers.
|--------------------------------------------------------------------------
*/

// Barcode lookup — qualquer usuário autenticado pode consultar (PDV)
Route::get('barcode/{value}', [BarcodeController::class, 'lookup'])->name('barcode.lookup');

// Import template — não requer autenticação especial
Route::get('products/import/template', [ProductImportController::class, 'template'])->name('products.import.template');

// Price lists — read
Route::get('price-lists',            [PriceListController::class, 'index'])->name('price-lists.index');
Route::get('price-lists/{priceList}', [PriceListController::class, 'show'])->name('price-lists.show');
Route::get('price-lists/{priceList}/prices', [PriceListController::class, 'prices'])->name('price-lists.prices');

// Products — QR Code endpoint (read)
Route::get('products/{product}/qrcode', [ProductController::class, 'qrcode'])->name('products.qrcode');

// Products — read (any authenticated user can search products)
Route::get('products',          [ProductController::class, 'index'])->name('products.index');
Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('products/{product}/variants',          [VariantController::class, 'index'])->name('products.variants.index');
Route::get('products/{product}/variants/{variant}', [VariantController::class, 'show'])->name('products.variants.show');
Route::get('products/{product}/price-history', [ProductPriceHistoryController::class, 'index'])->name('products.price-history.index');
Route::get('products/{product}/media',         [MediaAssetController::class, 'productMedia'])->name('products.media.index');
Route::get('products/{product}/commercial-collections', [ProductCollectionItemController::class, 'index'])->name('products.commercial-collections.index');

// Brands / Categories — read
Route::get('brands',         [BrandController::class, 'index'])->name('brands.index');
Route::get('brands/{brand}', [BrandController::class, 'show'])->name('brands.show');
Route::get('categories',             [CategoryController::class, 'index'])->name('categories.index');
Route::get('categories/{category}',  [CategoryController::class, 'show'])->name('categories.show');

// Attribute groups — read
Route::get('attribute-groups',                    [AttributeGroupController::class, 'index'])->name('attribute-groups.index');
Route::get('attribute-groups/{attributeGroup}',   [AttributeGroupController::class, 'show'])->name('attribute-groups.show');

// Grids — read
Route::get('grids',       [GridController::class, 'index'])->name('grids.index');
Route::get('grids/{grid}', [GridController::class, 'show'])->name('grids.show');

// Media assets — read
Route::get('media-assets',             [MediaAssetController::class, 'index'])->name('media-assets.index');
Route::get('media-assets/{mediaAsset}', [MediaAssetController::class, 'show'])->name('media-assets.show');

// Variants — read-only public lookup by uuid
Route::get('variants/generate', [VariantController::class, 'generate'])->name('variants.generate');

// Unit conversions — read + convert calculator (any authenticated user)
Route::get('unit-conversions',         [UnitConversionController::class, 'index'])->name('unit-conversions.index');
Route::post('unit-conversions/convert', [UnitConversionController::class, 'convert'])->name('unit-conversions.convert');

/*
|--------------------------------------------------------------------------
| Catalog — Write routes (products.view + specific create/update/delete)
| Wrapped in products.view so at minimum the user can see products before
| managing them.
|--------------------------------------------------------------------------
*/
Route::middleware('permission:products.view')->group(function (): void {

    // Products — write
    Route::post('products',           [ProductController::class, 'store'])->name('products.store');
    Route::put('products/{product}',  [ProductController::class, 'update'])->name('products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::post('products/{product}/publish',   [ProductController::class, 'publish'])->name('products.publish');
    Route::post('products/{product}/archive',   [ProductController::class, 'archive'])->name('products.archive');
    Route::post('products/{product}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');

    // Products — commercial collections (write)
    Route::post('products/{product}/commercial-collections',             [ProductCollectionItemController::class, 'attach'])->name('products.commercial-collections.attach');
    Route::delete('products/{product}/commercial-collections/{collection}', [ProductCollectionItemController::class, 'detach'])->name('products.commercial-collections.detach');

    // Products — media (write)
    Route::post('products/{product}/media',              [MediaAssetController::class, 'attachToProduct'])->name('products.media.attach');
    Route::delete('products/{product}/media/{mediaAsset}', [MediaAssetController::class, 'detachFromProduct'])->name('products.media.detach');

    // Variants — write
    Route::post('variants',          [VariantController::class, 'store'])->name('variants.store');
    Route::put('variants/{variant}', [VariantController::class, 'update'])->name('variants.update');
    Route::delete('products/{product}/variants/{variant}', [VariantController::class, 'destroy'])->name('products.variants.destroy');

    // Brands — write
    Route::post('brands',          [BrandController::class, 'store'])->name('brands.store');
    Route::put('brands/{brand}',   [BrandController::class, 'update'])->name('brands.update');
    Route::delete('brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');

    // Categories — write
    Route::post('categories',            [CategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{category}',  [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Collections
    Route::apiResource('collections', CollectionController::class);

    // Attribute Groups — write
    Route::post('attribute-groups',                    [AttributeGroupController::class, 'store'])->name('attribute-groups.store');
    Route::delete('attribute-groups/{attributeGroup}', [AttributeGroupController::class, 'destroy'])->name('attribute-groups.destroy');
    Route::post('attribute-groups/{attributeGroup}/attributes',             [AttributeGroupController::class, 'storeAttribute'])->name('attribute-groups.attributes.store');
    Route::delete('attribute-groups/{attributeGroup}/attributes/{attribute}', [AttributeGroupController::class, 'destroyAttribute'])->name('attribute-groups.attributes.destroy');

    // Grids — write
    Route::post('grids',        [GridController::class, 'store'])->name('grids.store');
    Route::delete('grids/{grid}', [GridController::class, 'destroy'])->name('grids.destroy');

    // Unit conversions — write
    Route::post('unit-conversions',                      [UnitConversionController::class, 'store'])->name('unit-conversions.store');
    Route::put('unit-conversions/{unitConversion}',      [UnitConversionController::class, 'update'])->name('unit-conversions.update');
    Route::delete('unit-conversions/{unitConversion}',   [UnitConversionController::class, 'destroy'])->name('unit-conversions.destroy');

    // Price lists — write
    Route::post('price-lists',            [PriceListController::class, 'store'])->name('price-lists.store');
    Route::put('price-lists/{priceList}', [PriceListController::class, 'update'])->name('price-lists.update');
    Route::delete('price-lists/{priceList}', [PriceListController::class, 'destroy'])->name('price-lists.destroy');
    Route::post('price-lists/{priceList}/prices', [PriceListController::class, 'upsertPrices'])->name('price-lists.prices.upsert');

    // Product import
    Route::post('products/import', [ProductImportController::class, 'import'])->name('products.import');

    // Images (legacy)
    Route::post('images',                  [ProductImageController::class, 'store'])->name('images.store');
    Route::delete('images/{productImage}', [ProductImageController::class, 'destroy'])->name('images.destroy');
    Route::post('images/reorder',          [ProductImageController::class, 'reorder'])->name('images.reorder');

    // Media Assets — write
    Route::post('media-assets',               [MediaAssetController::class, 'store'])->name('media-assets.store');
    Route::delete('media-assets/{mediaAsset}', [MediaAssetController::class, 'destroy'])->name('media-assets.destroy');
});
