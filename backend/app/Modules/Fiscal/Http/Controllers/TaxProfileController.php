<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Controllers;

use App\Modules\Fiscal\DTOs\CreateTaxProfileDTO;
use App\Modules\Fiscal\Http\Requests\CreateTaxProfileRequest;
use App\Modules\Fiscal\Http\Resources\TaxProfileResource;
use App\Modules\Fiscal\Models\TaxProfile;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class TaxProfileController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $profiles = TaxProfile::query()
            ->when($request->string('regime')->value(), fn ($q, $v) => $q->where('regime', $v))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 50));

        return $this->success(
            data: TaxProfileResource::collection($profiles),
            meta: [
                'current_page' => $profiles->currentPage(),
                'per_page'     => $profiles->perPage(),
                'total'        => $profiles->total(),
                'last_page'    => $profiles->lastPage(),
            ],
        );
    }

    public function store(CreateTaxProfileRequest $request): JsonResponse
    {
        $dto = CreateTaxProfileDTO::fromRequest($request);

        $profile = TaxProfile::create([
            'name'     => $dto->name,
            'regime'   => $dto->regime,
            'metadata' => $dto->metadata,
        ]);

        return $this->created(new TaxProfileResource($profile));
    }

    public function show(TaxProfile $taxProfile): JsonResponse
    {
        return $this->success(new TaxProfileResource($taxProfile));
    }

    public function update(CreateTaxProfileRequest $request, TaxProfile $taxProfile): JsonResponse
    {
        $taxProfile->update($request->only(['name', 'regime', 'metadata']));

        return $this->success(new TaxProfileResource($taxProfile->refresh()));
    }

    public function destroy(TaxProfile $taxProfile): JsonResponse
    {
        $taxProfile->delete();

        return $this->noContent();
    }
}
