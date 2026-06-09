<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Actions\CreateAttributeAction;
use App\Modules\Catalog\Actions\CreateAttributeGroupAction;
use App\Modules\Catalog\DTOs\CreateAttributeDTO;
use App\Modules\Catalog\DTOs\CreateAttributeGroupDTO;
use App\Modules\Catalog\Http\Requests\StoreAttributeGroupRequest;
use App\Modules\Catalog\Http\Requests\StoreAttributeRequest;
use App\Modules\Catalog\Http\Resources\AttributeGroupResource;
use App\Modules\Catalog\Http\Resources\AttributeResource;
use App\Modules\Catalog\Models\Attribute;
use App\Modules\Catalog\Models\AttributeGroup;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

final class AttributeGroupController extends Controller
{
    use HasApiResponse;

    public function index(): JsonResponse
    {
        $groups = AttributeGroup::with('attributes')->orderBy('sort_order')->get();

        return $this->success(AttributeGroupResource::collection($groups));
    }

    public function store(StoreAttributeGroupRequest $request, CreateAttributeGroupAction $action): JsonResponse
    {
        $group = $action->execute(CreateAttributeGroupDTO::fromRequest($request));

        return $this->created(new AttributeGroupResource($group));
    }

    public function show(AttributeGroup $attributeGroup): JsonResponse
    {
        $attributeGroup->load('attributes');

        return $this->success(new AttributeGroupResource($attributeGroup));
    }

    public function destroy(AttributeGroup $attributeGroup): JsonResponse
    {
        $attributeGroup->delete();

        return $this->noContent();
    }

    // ── Attributes (nested resource) ──────────────────────────────────────────

    public function storeAttribute(
        StoreAttributeRequest $request,
        AttributeGroup $attributeGroup,
        CreateAttributeAction $action,
    ): JsonResponse {
        $attribute = $action->execute(CreateAttributeDTO::fromRequest(
            $request->merge(['attribute_group_id' => $attributeGroup->uuid])
        ));

        return $this->created(new AttributeResource($attribute));
    }

    public function destroyAttribute(AttributeGroup $attributeGroup, Attribute $attribute): JsonResponse
    {
        $attribute->delete();

        return $this->noContent();
    }
}
