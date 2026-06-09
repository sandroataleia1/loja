<?php

declare(strict_types=1);

namespace App\Modules\Media\Http\Controllers;

use App\Modules\Catalog\Events\ProductMediaAttached;
use App\Modules\Catalog\Models\Product;
use App\Modules\Media\Actions\AttachMediaToProductAction;
use App\Modules\Media\Actions\StoreMediaAssetAction;
use App\Modules\Media\Enums\MediaTypeEnum;
use App\Modules\Media\Http\Requests\AttachProductMediaRequest;
use App\Modules\Media\Http\Requests\StoreMediaAssetRequest;
use App\Modules\Media\Http\Resources\MediaAssetResource;
use App\Modules\Media\Models\MediaAsset;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class MediaAssetController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MediaAsset::class);

        $assets = MediaAsset::query()
            ->when($request->string('type')->value(), fn ($q, $v) => $q->where('type', $v))
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: MediaAssetResource::collection($assets),
            meta: [
                'current_page' => $assets->currentPage(),
                'per_page'     => $assets->perPage(),
                'total'        => $assets->total(),
                'last_page'    => $assets->lastPage(),
            ],
        );
    }

    public function store(StoreMediaAssetRequest $request, StoreMediaAssetAction $action): JsonResponse
    {
        $this->authorize('create', MediaAsset::class);

        $asset = $action->execute(
            $request->file('file'),
            MediaTypeEnum::from($request->string('type')->toString()),
            $request->array('metadata') ?: null,
        );

        return $this->created(new MediaAssetResource($asset));
    }

    public function show(MediaAsset $mediaAsset): JsonResponse
    {
        $this->authorize('view', $mediaAsset);

        return $this->success(new MediaAssetResource($mediaAsset));
    }

    public function destroy(MediaAsset $mediaAsset): JsonResponse
    {
        $this->authorize('delete', $mediaAsset);

        $mediaAsset->update(['is_active' => false]);

        return $this->noContent();
    }

    // ── Product media sub-resource ─────────────────────────────────────────────

    public function attachToProduct(
        AttachProductMediaRequest $request,
        Product                   $product,
        AttachMediaToProductAction $action,
    ): JsonResponse {
        $this->authorize('update', $product);

        $asset = MediaAsset::findOrFail($request->string('media_asset_id')->toString());

        $action->execute(
            $product,
            $asset,
            $request->integer('position', 0),
            $request->boolean('is_primary', false),
        );

        return $this->success(
            MediaAssetResource::collection($product->media()->get()),
            'Mídia vinculada ao produto.',
        );
    }

    public function detachFromProduct(Product $product, MediaAsset $mediaAsset): JsonResponse
    {
        $this->authorize('update', $product);

        $product->media()->detach($mediaAsset->uuid);

        return $this->noContent();
    }

    public function productMedia(Product $product): JsonResponse
    {
        $media = $product->media()->get();

        return $this->success(MediaAssetResource::collection($media));
    }
}
