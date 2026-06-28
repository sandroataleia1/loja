<?php

declare(strict_types=1);

namespace App\Modules\Sellers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sellers\Models\SellerCommission;
use App\Modules\Sellers\Models\SellerProfile;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SellerCommissionController extends Controller
{
    use HasApiResponse;

    public function index(SellerProfile $seller): JsonResponse
    {
        return $this->success($seller->commissions()->paginate(12));
    }

    public function store(Request $request, SellerProfile $seller): JsonResponse
    {
        $validated = $request->validate([
            'reference_year'       => ['required', 'integer', 'min:2020', 'max:2100'],
            'reference_month'      => ['required', 'integer', 'min:1', 'max:12'],
            'gross_amount_cents'   => ['required', 'integer', 'min:0'],
            'commission_rate'      => ['required', 'numeric', 'min:0', 'max:100'],
            'discount_given_cents' => ['nullable', 'integer', 'min:0'],
            'notes'                => ['nullable', 'string'],
        ]);

        $commissionCents = (int) round($validated['gross_amount_cents'] * $validated['commission_rate'] / 100);
        $discountCents   = $validated['discount_given_cents'] ?? 0;

        $commission = SellerCommission::updateOrCreate(
            [
                'tenant_id'       => $seller->tenant_id,
                'seller_id'       => $seller->uuid,
                'reference_year'  => $validated['reference_year'],
                'reference_month' => $validated['reference_month'],
            ],
            array_merge($validated, [
                'tenant_id'            => $seller->tenant_id,
                'seller_id'            => $seller->uuid,
                'commission_cents'     => $commissionCents,
                'discount_given_cents' => $discountCents,
                'net_commission_cents' => $commissionCents - $discountCents,
            ]),
        );

        return $this->created($commission->refresh());
    }

    public function update(Request $request, SellerProfile $seller, SellerCommission $commission): JsonResponse
    {
        $validated = $request->validate([
            'status'  => ['required', 'string', 'in:pending,approved,paid'],
            'paid_at' => ['nullable', 'date'],
            'notes'   => ['nullable', 'string'],
        ]);

        if ($validated['status'] === 'paid' && empty($validated['paid_at'])) {
            $validated['paid_at'] = now();
        }

        $commission->update($validated);

        return $this->success($commission->refresh());
    }
}
