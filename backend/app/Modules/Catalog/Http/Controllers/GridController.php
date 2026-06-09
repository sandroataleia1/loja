<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Actions\CreateGridAction;
use App\Modules\Catalog\DTOs\CreateGridDTO;
use App\Modules\Catalog\Http\Requests\StoreGridRequest;
use App\Modules\Catalog\Http\Resources\GridResource;
use App\Modules\Catalog\Models\Grid;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

final class GridController extends Controller
{
    use HasApiResponse;

    public function index(): JsonResponse
    {
        $grids = Grid::with('attributes')->get();

        return $this->success(GridResource::collection($grids));
    }

    public function store(StoreGridRequest $request, CreateGridAction $action): JsonResponse
    {
        $grid = $action->execute(CreateGridDTO::fromRequest($request));

        return $this->created(new GridResource($grid->load('attributes')));
    }

    public function show(Grid $grid): JsonResponse
    {
        $grid->load('attributes', 'attributeGroup');

        return $this->success(new GridResource($grid));
    }

    public function destroy(Grid $grid): JsonResponse
    {
        $grid->delete();

        return $this->noContent();
    }
}
