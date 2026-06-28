<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\CustomerCard;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerCardController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function index(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return $this->success($customer->cards()->get());
    }

    public function store(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'card_brand' => ['required', 'string', 'in:' . implode(',', CustomerCard::BRANDS)],
            'notes'      => ['nullable', 'string', 'max:200'],
        ]);

        $card = CustomerCard::create(array_merge(
            ['tenant_id' => $customer->tenant_id, 'customer_id' => $customer->uuid],
            $validated,
        ));

        return $this->created($card);
    }

    public function destroy(Customer $customer, CustomerCard $card): JsonResponse
    {
        $this->authorize('update', $customer);
        $card->delete();

        return $this->noContent();
    }
}
