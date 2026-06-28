<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\CustomerInteraction;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerInteractionController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function index(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $interactions = $customer->interactions()
            ->with('createdBy:uuid,name')
            ->limit(50)
            ->get();

        return $this->success($interactions);
    }

    public function store(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'interaction_type' => ['required', 'string', 'in:' . implode(',', CustomerInteraction::TYPES)],
            'subject'          => ['nullable', 'string', 'max:200'],
            'description'      => ['required', 'string'],
            'outcome'          => ['nullable', 'string', 'max:100'],
            'interacted_at'    => ['nullable', 'date'],
        ]);

        $interaction = CustomerInteraction::create(array_merge(
            [
                'tenant_id'     => $customer->tenant_id,
                'customer_id'   => $customer->uuid,
                'created_by'    => $request->user()->uuid,
                'interacted_at' => $validated['interacted_at'] ?? now(),
            ],
            $validated,
        ));

        return $this->created($interaction->load('createdBy:uuid,name'));
    }

    public function destroy(Customer $customer, CustomerInteraction $interaction): JsonResponse
    {
        $this->authorize('update', $customer);

        $interaction->delete();

        return $this->noContent();
    }
}
