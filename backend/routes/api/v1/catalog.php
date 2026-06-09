<?php

declare(strict_types=1);

use App\Modules\Catalog\Http\Controllers\AttributeGroupController;
use App\Modules\Catalog\Http\Controllers\BrandController;
use App\Modules\Catalog\Http\Controllers\CategoryController;
use App\Modules\Catalog\Http\Controllers\CollectionController;
use App\Modules\Catalog\Http\Controllers\GridController;
use App\Modules\Catalog\Http\Controllers\ProductCollectionItemController;
use App\Modules\Catalog\Http\Controllers\ProductController;
use App\Modules\Catalog\Http\Controllers\ProductImageController;
use App\Modules\Catalog\Http\Controllers\ProductPriceHistoryController;
use App\Modules\Catalog\Http\Controllers\VariantController;
use App\Modules\Media\Http\Controllers\MediaAssetController;
use Illuminate\Support\Facades\Route;

// ── Brands ────────────────────────────────────────────────────────────────────
Route::apiResource('brands', BrandController::class);

// ── Categories ────────────────────────────────────────────────────────────────
Route::apiResource('categories', CategoryController::class);

// ── Collections ───────────────────────────────────────────────────────────────
Route::apiResource('collections', CollectionController::class);

// ── Attribute Groups + Attributes (nested) ────────────────────────────────────
Route::apiResource('attribute-groups', AttributeGroupController::class)
    ->except(['update']);

Route::post(
    'attribute-groups/{attributeGroup}/attributes',
    [AttributeGroupController::class, 'storeAttribute'],
)->name('attribute-groups.attributes.store');

Route::delete(
    'attribute-groups/{attributeGroup}/attributes/{attribute}',
    [AttributeGroupController::class, 'destroyAttribute'],
)->name('attribute-groups.attributes.destroy');

// ── Grids ─────────────────────────────────────────────────────────────────────
Route::apiResource('grids', GridController::class)->except(['update']);

// ── Products ──────────────────────────────────────────────────────────────────
Route::apiResource('products', ProductController::class);

Route::post('products/{product}/publish',   [ProductController::class, 'publish'])->name('products.publish');
Route::post('products/{product}/archive',   [ProductController::class, 'archive'])->name('products.archive');
Route::post('products/{product}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');

// ── Product — Commercial Collections (many-to-many) ──────────────────────────
Route::get(
    'products/{product}/commercial-collections',
    [ProductCollectionItemController::class, 'index'],
)->name('products.commercial-collections.index');

Route::post(
    'products/{product}/commercial-collections',
    [ProductCollectionItemController::class, 'attach'],
)->name('products.commercial-collections.attach');

Route::delete(
    'products/{product}/commercial-collections/{collection}',
    [ProductCollectionItemController::class, 'detach'],
)->name('products.commercial-collections.detach');

// ── Product — Price History ───────────────────────────────────────────────────
Route::get(
    'products/{product}/price-history',
    [ProductPriceHistoryController::class, 'index'],
)->name('products.price-history.index');

// ── Product — Media (desacoplado) ────────────────────────────────────────────
Route::get(
    'products/{product}/media',
    [MediaAssetController::class, 'productMedia'],
)->name('products.media.index');

Route::post(
    'products/{product}/media',
    [MediaAssetController::class, 'attachToProduct'],
)->name('products.media.attach');

Route::delete(
    'products/{product}/media/{mediaAsset}',
    [MediaAssetController::class, 'detachFromProduct'],
)->name('products.media.detach');

// ── Variants ──────────────────────────────────────────────────────────────────
Route::get(
    'products/{product}/variants',
    [VariantController::class, 'index'],
)->name('products.variants.index');

Route::get(
    'products/{product}/variants/{variant}',
    [VariantController::class, 'show'],
)->name('products.variants.show');

Route::delete(
    'products/{product}/variants/{variant}',
    [VariantController::class, 'destroy'],
)->name('products.variants.destroy');

Route::post('variants',          [VariantController::class, 'store'])->name('variants.store');
Route::put('variants/{variant}', [VariantController::class, 'update'])->name('variants.update');
Route::post('variants/generate', [VariantController::class, 'generate'])->name('variants.generate');

// ── Images (legacy URL-based) ─────────────────────────────────────────────────
Route::post('images',                  [ProductImageController::class, 'store'])->name('images.store');
Route::delete('images/{productImage}', [ProductImageController::class, 'destroy'])->name('images.destroy');
Route::post('images/reorder',          [ProductImageController::class, 'reorder'])->name('images.reorder');

// ── Media Assets (decoupled media domain) ─────────────────────────────────────
Route::apiResource('media-assets', MediaAssetController::class)
    ->only(['index', 'store', 'show', 'destroy'])
    ->parameters(['media-assets' => 'mediaAsset']);
