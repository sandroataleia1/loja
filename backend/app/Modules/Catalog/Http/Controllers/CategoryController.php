<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\DTOs\CreateCategoryDTO;
use App\Modules\Catalog\Http\Requests\StoreCategoryRequest;
use App\Modules\Catalog\Http\Resources\CategoryResource;
use App\Modules\Catalog\Models\Category;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

final class CategoryController extends Controller
{
    use HasApiResponse;

    public function index(): JsonResponse
    {
        // Retorna árvore de categorias raiz com filhos diretos
        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        return $this->success(CategoryResource::collection($categories));
    }

    public function store(StoreCategoryRequest $request, CreateCategoryAction $action): JsonResponse
    {
        $category = $action->execute(CreateCategoryDTO::fromRequest($request));

        return $this->created(new CategoryResource($category));
    }

    public function show(Category $category): JsonResponse
    {
        $category->load('children', 'parent');

        return $this->success(new CategoryResource($category));
    }

    public function update(StoreCategoryRequest $request, Category $category, CreateCategoryAction $action): JsonResponse
    {
        $category->update($request->validated());

        return $this->success(new CategoryResource($category->refresh()));
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return $this->noContent();
    }
}
