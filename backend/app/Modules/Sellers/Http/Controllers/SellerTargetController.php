<?php

declare(strict_types=1);

namespace App\Modules\Sellers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sellers\Models\SellerProfile;
use App\Modules\Sellers\Models\SellerTarget;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SellerTargetController extends Controller
{
    use HasApiResponse;

    public function index(SellerProfile $seller): JsonResponse
    {
        return $this->success($seller->targets()->get()->map(fn ($t) => array_merge(
            $t->toArray(),
            ['achievement_percent' => $t->achievementPercent(), 'is_achieved' => $t->isAchieved()],
        )));
    }

    public function store(Request $request, SellerProfile $seller): JsonResponse
    {
        $validated = $request->validate([
            'year'                     => ['required', 'integer', 'min:2020', 'max:2100'],
            'month'                    => ['required', 'integer', 'min:1', 'max:12'],
            'target_cents'             => ['required', 'integer', 'min:0'],
            'commission_rate_override' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $target = SellerTarget::updateOrCreate(
            ['tenant_id' => $seller->tenant_id, 'seller_id' => $seller->uuid, 'year' => $validated['year'], 'month' => $validated['month']],
            array_merge($validated, ['tenant_id' => $seller->tenant_id, 'seller_id' => $seller->uuid]),
        );

        return $this->created(array_merge(
            $target->toArray(),
            ['achievement_percent' => $target->achievementPercent(), 'is_achieved' => $target->isAchieved()],
        ));
    }

    public function destroy(SellerProfile $seller, SellerTarget $target): JsonResponse
    {
        $target->delete();

        return $this->noContent();
    }
}
