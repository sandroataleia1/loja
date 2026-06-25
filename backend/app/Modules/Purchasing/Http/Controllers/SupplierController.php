<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Http\Controllers;

use App\Core\Tenancy\Rules\ValidCnpj;
use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Purchasing\Actions\CreateSupplierAction;
use App\Modules\Purchasing\Http\Resources\SupplierAddressResource;
use App\Modules\Purchasing\Http\Resources\SupplierContactResource;
use App\Modules\Purchasing\Http\Resources\SupplierResource;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Purchasing\Models\SupplierAddress;
use App\Modules\Purchasing\Models\SupplierContact;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SupplierController extends Controller
{
    use HasApiResponse;

    // ── CRUD ─────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->when($request->boolean('active'), fn ($q) => $q->where('is_active', true))
            ->when($request->filled('q'), function ($q) use ($request) {
                $v = $request->string('q')->toString();
                return $q->whereRaw(
                    "search_vector @@ plainto_tsquery('portuguese', ?)",
                    [$v]
                )->orWhere('code', 'ilike', "%{$v}%");
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: SupplierResource::collection($suppliers),
            meta: [
                'current_page' => $suppliers->currentPage(),
                'per_page'     => $suppliers->perPage(),
                'total'        => $suppliers->total(),
                'last_page'    => $suppliers->lastPage(),
            ],
        );
    }

    public function store(Request $request, CreateSupplierAction $action): JsonResponse
    {
        $isCompany = $request->input('person_type') === 'COMPANY';
        $validated = $request->validate([
            'person_type' => ['required', 'in:INDIVIDUAL,COMPANY'],
            'name'        => ['required', 'string', 'max:200'],
            'trade_name'  => ['nullable', 'string', 'max:200'],
            'document'    => ['nullable', 'string', 'max:20', ...($isCompany ? [new ValidCnpj()] : [])],
            'ie'          => ['nullable', 'string', 'max:30'],
            'im'          => ['nullable', 'string', 'max:20'],
            'email'       => ['nullable', 'email', 'max:254'],
            'phone'       => ['nullable', 'string', 'max:30'],
            'notes'       => ['nullable', 'string'],
            'is_active'   => ['boolean'],
        ]);

        $supplier = $action->execute($validated);

        return $this->created(new SupplierResource($supplier));
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return $this->success(new SupplierResource($supplier->load(['addresses', 'contacts'])));
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $isCompany = $request->input('person_type', $supplier->person_type) === 'COMPANY';
        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:200'],
            'trade_name'  => ['nullable', 'string', 'max:200'],
            'document'    => ['nullable', 'string', 'max:20', ...($isCompany ? [new ValidCnpj()] : [])],
            'ie'          => ['nullable', 'string', 'max:30'],
            'im'          => ['nullable', 'string', 'max:20'],
            'email'       => ['nullable', 'email', 'max:254'],
            'phone'       => ['nullable', 'string', 'max:30'],
            'notes'       => ['nullable', 'string'],
            'is_active'   => ['boolean'],
        ]);

        $supplier->update($validated);

        return $this->success(new SupplierResource($supplier->refresh()->load(['addresses', 'contacts'])));
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();
        return $this->noContent();
    }

    // ── Addresses ────────────────────────────────────────────────────────────

    public function addresses(Supplier $supplier): JsonResponse
    {
        return $this->success(SupplierAddressResource::collection($supplier->addresses));
    }

    public function storeAddress(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'zipcode'    => ['required', 'string', 'max:10'],
            'street'     => ['required', 'string', 'max:200'],
            'number'     => ['required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:100'],
            'district'   => ['required', 'string', 'max:100'],
            'city'       => ['required', 'string', 'max:100'],
            'state'      => ['required', 'string', 'size:2'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($validated['is_default'] ?? false) {
            $supplier->addresses()->update(['is_default' => false]);
        }

        $address = SupplierAddress::create(array_merge(
            ['supplier_id' => $supplier->uuid, 'country' => 'BR'],
            $validated,
        ));

        return $this->created(new SupplierAddressResource($address));
    }

    public function updateAddress(Request $request, Supplier $supplier, SupplierAddress $address): JsonResponse
    {
        $validated = $request->validate([
            'zipcode'    => ['sometimes', 'string', 'max:10'],
            'street'     => ['sometimes', 'string', 'max:200'],
            'number'     => ['sometimes', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:100'],
            'district'   => ['sometimes', 'string', 'max:100'],
            'city'       => ['sometimes', 'string', 'max:100'],
            'state'      => ['sometimes', 'string', 'size:2'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($validated['is_default'] ?? false) {
            $supplier->addresses()->where('uuid', '!=', $address->uuid)->update(['is_default' => false]);
        }

        $address->update($validated);

        return $this->success(new SupplierAddressResource($address->refresh()));
    }

    public function destroyAddress(Supplier $supplier, SupplierAddress $address): JsonResponse
    {
        $address->delete();
        return $this->noContent();
    }

    // ── Contacts ─────────────────────────────────────────────────────────────

    public function contacts(Supplier $supplier): JsonResponse
    {
        return $this->success(SupplierContactResource::collection($supplier->contacts));
    }

    public function storeContact(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'type'       => ['required', 'string', 'in:PHONE,WHATSAPP,EMAIL,OTHER'],
            'value'      => ['required', 'string', 'max:200'],
            'label'      => ['nullable', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $contact = SupplierContact::create(array_merge(
            ['supplier_id' => $supplier->uuid],
            $validated,
        ));

        return $this->created(new SupplierContactResource($contact));
    }

    public function updateContact(Request $request, Supplier $supplier, SupplierContact $contact): JsonResponse
    {
        $validated = $request->validate([
            'type'       => ['sometimes', 'required', 'string', 'in:PHONE,WHATSAPP,EMAIL,OTHER'],
            'value'      => ['sometimes', 'required', 'string', 'max:200'],
            'label'      => ['nullable', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $contact->update($validated);

        return $this->success(new SupplierContactResource($contact->refresh()));
    }

    public function destroyContact(Supplier $supplier, SupplierContact $contact): JsonResponse
    {
        $contact->delete();
        return $this->noContent();
    }
}
