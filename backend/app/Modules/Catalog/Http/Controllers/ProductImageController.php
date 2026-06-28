<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Actions\AttachImageAction;
use App\Modules\Catalog\Actions\ReorderImagesAction;
use App\Modules\Catalog\DTOs\AttachImageDTO;
use App\Modules\Catalog\Http\Requests\AttachImageRequest;
use App\Modules\Catalog\Http\Requests\ReorderImagesRequest;
use App\Modules\Catalog\Http\Resources\ImageResource;
use App\Modules\Catalog\Models\ProductImage;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

final class ProductImageController extends Controller
{
    use HasApiResponse;

    public function store(AttachImageRequest $request, AttachImageAction $action): JsonResponse
    {
        $image = $action->execute(AttachImageDTO::fromRequest($request));

        return $this->created(new ImageResource($image));
    }

    public function update(Request $request, ProductImage $productImage): JsonResponse
    {
        $validated = $request->validate([
            'is_primary' => ['sometimes', 'boolean'],
            'alt_text'   => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated, $productImage): void {
            if (isset($validated['is_primary']) && $validated['is_primary'] === true) {
                ProductImage::where('imageable_id', $productImage->imageable_id)
                    ->where('imageable_type', $productImage->imageable_type)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $productImage->update(array_filter($validated, fn ($v) => $v !== null || array_key_exists('alt_text', $validated)));
        });

        return $this->success(new ImageResource($productImage->fresh()));
    }

    public function destroy(ProductImage $productImage): JsonResponse
    {
        $productImage->delete();

        return $this->noContent();
    }

    public function reorder(ReorderImagesRequest $request, ReorderImagesAction $action): JsonResponse
    {
        $action->execute(
            imageableId:   $request->string('imageable_id')->toString(),
            imageableType: $request->string('imageable_type')->toString(),
            orderedUuids:  $request->array('uuids'),
        );

        return $this->success(message: 'Ordem atualizada com sucesso.');
    }
}
