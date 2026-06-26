<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Customers\Actions\CreateCustomerAction;
use App\Modules\Customers\Actions\UpdateCustomerAction;
use App\Modules\Customers\DTOs\CreateCustomerDTO;
use App\Modules\Customers\DTOs\UpdateCustomerDTO;
use App\Modules\Customers\Events\CustomerDeleted;
use App\Modules\Customers\Http\Requests\StoreCustomerRequest;
use App\Modules\Customers\Http\Requests\UpdateCustomerRequest;
use App\Modules\Customers\Http\Resources\CustomerAddressResource;
use App\Modules\Customers\Http\Resources\CustomerContactResource;
use App\Modules\Customers\Http\Resources\CustomerResource;
use App\Modules\Customers\Http\Resources\CustomerTagResource;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\CustomerAddress;
use App\Modules\Customers\Models\CustomerContact;
use App\Modules\Customers\Models\CustomerTag;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class CustomerController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $tenantId = TenantContext::getIdOrFail();

        $query = Customer::where('tenant_id', $tenantId)
            ->when(
                ! $request->boolean('include_default'),
                fn ($q) => $q->where('is_default_consumer', false)
            )
            ->when(
                $request->filled('q'),
                function ($q) use ($request) {
                    $v = $request->string('q')->toString();
                    return $q->whereRaw(
                        "search_vector @@ plainto_tsquery('portuguese', ?)",
                        [$v]
                    )->orWhere('code', 'ilike', "%{$v}%")
                     ->orWhere('email', 'ilike', "%{$v}%");
                }
            )
            ->when(
                $request->filled('document'),
                fn ($q) => $q->where('document', $request->input('document'))
            )
            ->when(
                $request->filled('email'),
                fn ($q) => $q->where('email', $request->input('email'))
            )
            ->when(
                $request->filled('tag'),
                fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('customer_tags.uuid', $request->input('tag')))
            )
            ->when(
                $request->boolean('active'),
                fn ($q) => $q->where('is_active', true)
            )
            ->with(['contacts' => fn ($q) => $q->where('is_primary', true)])
            ->orderBy('name');

        $customers = $query->paginate($request->integer('per_page', 20));

        return $this->success(
            data: CustomerResource::collection($customers),
            meta: [
                'current_page' => $customers->currentPage(),
                'per_page'     => $customers->perPage(),
                'total'        => $customers->total(),
                'last_page'    => $customers->lastPage(),
            ],
        );
    }

    public function store(StoreCustomerRequest $request, CreateCustomerAction $action): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $customer = $action->execute(CreateCustomerDTO::fromRequest($request));

        return $this->created(new CustomerResource($customer->load(['addresses', 'contacts', 'tags', 'commercialReferences'])));
    }

    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return $this->success(new CustomerResource($customer->load(['addresses', 'contacts', 'tags', 'commercialReferences'])));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer, UpdateCustomerAction $action): JsonResponse
    {
        $this->authorize('update', $customer);

        $updated = $action->execute($customer, UpdateCustomerDTO::fromRequest($request));

        return $this->success(new CustomerResource($updated->load(['addresses', 'contacts', 'tags'])));
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);

        $tenantId = TenantContext::getIdOrFail();
        $actorId  = auth()->id();

        $customer->delete();

        event(new CustomerDeleted(
            tenantId:     $tenantId,
            customerId:   $customer->uuid,
            customerName: $customer->name,
            actorId:      $actorId,
        ));

        return $this->noContent();
    }

    // ─── Address sub-resource ────────────────────────────────────────────────

    public function addresses(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return $this->success(CustomerAddressResource::collection($customer->addresses));
    }

    public function storeAddress(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'zipcode'    => ['required', 'string', 'max:10'],
            'street'     => ['required', 'string', 'max:200'],
            'number'     => ['required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:100'],
            'district'   => ['required', 'string', 'max:100'],
            'city'       => ['required', 'string', 'max:100'],
            'state'      => ['required', 'string', 'size:2'],
            'country'    => ['nullable', 'string', 'size:2'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($validated['is_default'] ?? false) {
            $customer->addresses()->update(['is_default' => false]);
        }

        $address = CustomerAddress::create(array_merge(
            ['customer_id' => $customer->uuid, 'country' => 'BR'],
            $validated,
        ));

        return $this->created(new CustomerAddressResource($address));
    }

    public function updateAddress(Request $request, Customer $customer, CustomerAddress $address): JsonResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'zipcode'    => ['sometimes', 'required', 'string', 'max:10'],
            'street'     => ['sometimes', 'required', 'string', 'max:200'],
            'number'     => ['sometimes', 'required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:100'],
            'district'   => ['sometimes', 'required', 'string', 'max:100'],
            'city'       => ['sometimes', 'required', 'string', 'max:100'],
            'state'      => ['sometimes', 'required', 'string', 'size:2'],
            'country'    => ['nullable', 'string', 'size:2'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($validated['is_default'] ?? false) {
            $customer->addresses()->where('uuid', '!=', $address->uuid)->update(['is_default' => false]);
        }

        $address->update($validated);

        return $this->success(new CustomerAddressResource($address->refresh()));
    }

    public function destroyAddress(Customer $customer, CustomerAddress $address): JsonResponse
    {
        $this->authorize('update', $customer);

        $address->delete();

        return $this->noContent();
    }

    // ─── Contact sub-resource ────────────────────────────────────────────────

    public function contacts(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return $this->success(CustomerContactResource::collection($customer->contacts));
    }

    public function storeContact(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'type'       => ['required', 'string', 'in:PHONE,WHATSAPP,EMAIL,INSTAGRAM,OTHER'],
            'value'      => ['required', 'string', 'max:200'],
            'label'      => ['nullable', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $contact = CustomerContact::create(array_merge(
            ['customer_id' => $customer->uuid],
            $validated,
        ));

        return $this->created(new CustomerContactResource($contact));
    }

    public function updateContact(Request $request, Customer $customer, CustomerContact $contact): JsonResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'type'       => ['sometimes', 'required', 'string', 'in:PHONE,WHATSAPP,EMAIL,INSTAGRAM,OTHER'],
            'value'      => ['sometimes', 'required', 'string', 'max:200'],
            'label'      => ['nullable', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $contact->update($validated);

        return $this->success(new CustomerContactResource($contact->refresh()));
    }

    public function destroyContact(Customer $customer, CustomerContact $contact): JsonResponse
    {
        $this->authorize('update', $customer);

        $contact->delete();

        return $this->noContent();
    }

    // ─── Tag sub-resource ────────────────────────────────────────────────────

    public function attachTag(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'tag_id' => ['required', 'uuid', 'exists:customer_tags,uuid'],
        ]);

        $customer->tags()->syncWithoutDetaching([$validated['tag_id']]);

        return $this->success(CustomerTagResource::collection($customer->tags));
    }

    public function detachTag(Customer $customer, CustomerTag $tag): JsonResponse
    {
        $this->authorize('update', $customer);

        $customer->tags()->detach($tag->uuid);

        return $this->noContent();
    }
}
