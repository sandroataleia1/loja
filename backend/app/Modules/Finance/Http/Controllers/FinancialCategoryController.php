<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Modules\Finance\Http\Requests\CreateFinancialCategoryRequest;
use App\Modules\Finance\Http\Resources\FinancialCategoryResource;
use App\Modules\Finance\Models\FinancialCategory;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class FinancialCategoryController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $categories = FinancialCategory::query()
            ->when($request->string('type')->value(), fn ($q, $v) => $q->where('type', $v))
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 50));

        return $this->success(
            data: FinancialCategoryResource::collection($categories),
            meta: [
                'current_page' => $categories->currentPage(),
                'per_page'     => $categories->perPage(),
                'total'        => $categories->total(),
                'last_page'    => $categories->lastPage(),
            ],
        );
    }

    public function store(CreateFinancialCategoryRequest $request): JsonResponse
    {
        $category = FinancialCategory::create([
            'name'      => $request->string('name')->toString(),
            'type'      => $request->string('type')->toString(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->created(new FinancialCategoryResource($category));
    }

    public function show(FinancialCategory $financialCategory): JsonResponse
    {
        return $this->success(new FinancialCategoryResource($financialCategory));
    }

    public function update(CreateFinancialCategoryRequest $request, FinancialCategory $financialCategory): JsonResponse
    {
        $financialCategory->update($request->only(['name', 'type', 'is_active']));

        return $this->success(new FinancialCategoryResource($financialCategory->refresh()));
    }

    public function destroy(FinancialCategory $financialCategory): JsonResponse
    {
        $financialCategory->delete();

        return $this->noContent();
    }
}
