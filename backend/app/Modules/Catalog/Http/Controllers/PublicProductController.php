<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductShareLink;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;

final class PublicProductController extends Controller
{
    use HasApiResponse;

    public function show(string $token): JsonResponse
    {
        /** @var ProductShareLink $link */
        $link = ProductShareLink::withoutGlobalScopes()->where('token', $token)->firstOrFail();

        if ($link->isExpired()) {
            return $this->error('Link expirado.', status: 410);
        }

        $link->increment('view_count');

        // Set tenant context so BelongsToTenant scopes work when loading relations
        TenantContext::set($link->tenant_id);

        $product = Product::withoutGlobalScopes()
            ->where('uuid', $link->product_id)
            ->firstOrFail();

        $product->loadMissing(['brand', 'categories', 'variants', 'images']);

        return $this->success(new ProductResource($product));
    }
}
