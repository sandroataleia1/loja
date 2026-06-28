<?php

declare(strict_types=1);

namespace App\Modules\Sellers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sellers\Models\SellerProfile;
use App\Modules\Sellers\Models\SellerRegion;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SellerRegionController extends Controller
{
    use HasApiResponse;

    public function index(SellerProfile $seller): JsonResponse
    {
        return $this->success($seller->regions()->get());
    }

    public function store(Request $request, SellerProfile $seller): JsonResponse
    {
        $validated = $request->validate([
            'region_type' => ['required', 'string', 'in:' . implode(',', SellerRegion::TYPES)],
            'value'       => ['required', 'string', 'max:100'],
        ]);

        $region = SellerRegion::create(array_merge(
            ['tenant_id' => $seller->tenant_id, 'seller_id' => $seller->uuid],
            $validated,
        ));

        return $this->created($region);
    }

    public function destroy(SellerProfile $seller, SellerRegion $region): JsonResponse
    {
        $region->delete();

        return $this->noContent();
    }
}
