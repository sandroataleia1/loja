<?php

declare(strict_types=1);

namespace App\Modules\Conditional\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Conditional\Actions\CancelConditionalAction;
use App\Modules\Conditional\Actions\ConvertConditionalAction;
use App\Modules\Conditional\Actions\OpenConditionalAction;
use App\Modules\Conditional\Actions\ReturnConditionalAction;
use App\Modules\Conditional\DTOs\OpenConditionalDTO;
use App\Modules\Conditional\Http\Requests\StoreConditionalRequest;
use App\Modules\Conditional\Http\Resources\ConditionalResource;
use App\Modules\Conditional\Models\Conditional;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class ConditionalController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $query = Conditional::where('tenant_id', $tenantId)
            ->with(['customer', 'store'])
            ->when($request->filled('status'),      fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('store_id'),    fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('q'),           fn ($q) => $q->where('code', 'ilike', "%{$request->q}%"))
            ->when(
                $request->boolean('overdue'),
                fn ($q) => $q->where('expires_at', '<', now())
                    ->whereNotIn('status', ['returned', 'converted', 'cancelled'])
            )
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: ConditionalResource::collection($query),
            meta: [
                'current_page' => $query->currentPage(),
                'per_page'     => $query->perPage(),
                'total'        => $query->total(),
                'last_page'    => $query->lastPage(),
            ],
        );
    }

    public function store(StoreConditionalRequest $request, OpenConditionalAction $action): JsonResponse
    {
        $this->authorize('create', Conditional::class);
        $conditional = $action->execute(OpenConditionalDTO::fromRequest($request));

        return $this->created(new ConditionalResource($conditional));
    }

    public function show(Conditional $conditional): JsonResponse
    {
        $this->authorize('view', $conditional);

        return $this->success(
            new ConditionalResource(
                $conditional->load(['items.variant', 'customer', 'store', 'statusHistory'])
            )
        );
    }

    public function return(Request $request, Conditional $conditional, ReturnConditionalAction $action): JsonResponse
    {
        $this->authorize('update', $conditional);

        $validated = $request->validate([
            'returns'              => ['required', 'array', 'min:1'],
            'returns.*.item_uuid'  => ['required', 'uuid'],
            'returns.*.quantity'   => ['required', 'integer', 'min:1'],
        ]);

        $updated = $action->execute($conditional, $validated['returns']);

        return $this->success(
            new ConditionalResource($updated->load(['items.variant', 'statusHistory']))
        );
    }

    public function convert(Request $request, Conditional $conditional, ConvertConditionalAction $action): JsonResponse
    {
        $this->authorize('update', $conditional);

        $validated = $request->validate([
            'conversions'              => ['required', 'array', 'min:1'],
            'conversions.*.item_uuid'  => ['required', 'uuid'],
            'conversions.*.quantity'   => ['required', 'integer', 'min:1'],
        ]);

        $updated = $action->execute($conditional, $validated['conversions']);

        return $this->success(
            new ConditionalResource($updated->load(['items.variant', 'statusHistory']))
        );
    }

    public function cancel(Conditional $conditional, CancelConditionalAction $action): JsonResponse
    {
        $this->authorize('delete', $conditional);
        $updated = $action->execute($conditional);

        return $this->success(
            new ConditionalResource($updated->load(['items', 'statusHistory']))
        );
    }
}
