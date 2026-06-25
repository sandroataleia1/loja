<?php

declare(strict_types=1);

namespace App\Modules\Carriers\Http\Controllers;

use App\Core\Tenancy\Rules\ValidCnpj;
use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Carriers\Http\Resources\CarrierAddressResource;
use App\Modules\Carriers\Http\Resources\CarrierContactResource;
use App\Modules\Carriers\Http\Resources\CarrierResource;
use App\Modules\Carriers\Models\Carrier;
use App\Modules\Carriers\Models\CarrierAddress;
use App\Modules\Carriers\Models\CarrierContact;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CarrierController extends Controller
{
    use HasApiResponse;

    private const ADDRESS_RULES = [
        'zipcode'    => ['required', 'string', 'max:10'],
        'street'     => ['required', 'string', 'max:200'],
        'number'     => ['required', 'string', 'max:20'],
        'complement' => ['nullable', 'string', 'max:100'],
        'district'   => ['required', 'string', 'max:100'],
        'city'       => ['required', 'string', 'max:100'],
        'state'      => ['required', 'string', 'size:2'],
        'is_default' => ['nullable', 'boolean'],
    ];

    private const CONTACT_RULES = [
        'type'       => ['required', 'string', 'in:PHONE,WHATSAPP,EMAIL,OTHER'],
        'value'      => ['required', 'string', 'max:200'],
        'label'      => ['nullable', 'string', 'max:100'],
        'is_primary' => ['nullable', 'boolean'],
    ];

    // ── CRUD ─────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $carriers = Carrier::where('tenant_id', $tenantId)
            ->when($request->boolean('active'), fn ($q) => $q->where('is_active', true))
            ->when($request->filled('q'), fn ($q) => $q->where(
                fn ($sub) => $sub->where('name',  'ilike', "%{$request->q}%")
                                  ->orWhere('cnpj', 'ilike', "%{$request->q}%")
                                  ->orWhere('code', 'ilike', "%{$request->q}%")
            ))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: CarrierResource::collection($carriers),
            meta: [
                'current_page' => $carriers->currentPage(),
                'per_page'     => $carriers->perPage(),
                'total'        => $carriers->total(),
                'last_page'    => $carriers->lastPage(),
            ],
        );
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId  = TenantContext::getIdOrFail();
        $validated = $request->validate([
            'code'          => ['nullable', 'string', 'max:20'],
            'name'          => ['required', 'string', 'max:200'],
            'trade_name'    => ['nullable', 'string', 'max:200'],
            'cnpj'          => ['nullable', 'string', 'max:20', new ValidCnpj()],
            'ie'            => ['nullable', 'string', 'max:30'],
            'email'         => ['nullable', 'email', 'max:254'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'notes'         => ['nullable', 'string'],
            'is_active'     => ['boolean'],
            'delivery_mode' => ['nullable', 'string', 'in:own_fleet,outsourced,motorcycle,post_office,app'],
            'rntrc'         => ['nullable', 'string', 'max:20'],
        ]);

        $carrier = Carrier::create(array_merge(['tenant_id' => $tenantId], $validated));

        return $this->created(new CarrierResource($carrier));
    }

    public function show(Carrier $carrier): JsonResponse
    {
        return $this->success(
            new CarrierResource($carrier->load(['addresses', 'contacts']))
        );
    }

    public function update(Request $request, Carrier $carrier): JsonResponse
    {
        $validated = $request->validate([
            'code'          => ['nullable', 'string', 'max:20'],
            'name'          => ['sometimes', 'required', 'string', 'max:200'],
            'trade_name'    => ['nullable', 'string', 'max:200'],
            'cnpj'          => ['nullable', 'string', 'max:20', new ValidCnpj()],
            'ie'            => ['nullable', 'string', 'max:30'],
            'email'         => ['nullable', 'email', 'max:254'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'notes'         => ['nullable', 'string'],
            'is_active'     => ['boolean'],
            'delivery_mode' => ['nullable', 'string', 'in:own_fleet,outsourced,motorcycle,post_office,app'],
            'rntrc'         => ['nullable', 'string', 'max:20'],
        ]);

        $carrier->update($validated);

        return $this->success(new CarrierResource($carrier->refresh()->load(['addresses', 'contacts'])));
    }

    public function destroy(Carrier $carrier): JsonResponse
    {
        $carrier->delete();
        return $this->noContent();
    }

    // ── Addresses ────────────────────────────────────────────────────────────

    public function addresses(Carrier $carrier): JsonResponse
    {
        return $this->success(CarrierAddressResource::collection($carrier->addresses));
    }

    public function storeAddress(Request $request, Carrier $carrier): JsonResponse
    {
        $validated = $request->validate(self::ADDRESS_RULES);

        if ($validated['is_default'] ?? false) {
            $carrier->addresses()->update(['is_default' => false]);
        }

        $address = CarrierAddress::create(array_merge(
            ['carrier_id' => $carrier->uuid, 'country' => 'BR'],
            $validated,
        ));

        return $this->created(new CarrierAddressResource($address));
    }

    public function updateAddress(Request $request, Carrier $carrier, CarrierAddress $address): JsonResponse
    {
        $validated = $request->validate(array_map(
            fn ($rules) => array_merge(['sometimes'], array_filter($rules, fn ($r) => $r !== 'required')),
            self::ADDRESS_RULES,
        ));

        if ($validated['is_default'] ?? false) {
            $carrier->addresses()->where('uuid', '!=', $address->uuid)->update(['is_default' => false]);
        }

        $address->update($validated);

        return $this->success(new CarrierAddressResource($address->refresh()));
    }

    public function destroyAddress(Carrier $carrier, CarrierAddress $address): JsonResponse
    {
        $address->delete();
        return $this->noContent();
    }

    // ── Contacts ─────────────────────────────────────────────────────────────

    public function contacts(Carrier $carrier): JsonResponse
    {
        return $this->success(CarrierContactResource::collection($carrier->contacts));
    }

    public function storeContact(Request $request, Carrier $carrier): JsonResponse
    {
        $validated = $request->validate(self::CONTACT_RULES);

        $contact = CarrierContact::create(array_merge(
            ['carrier_id' => $carrier->uuid],
            $validated,
        ));

        return $this->created(new CarrierContactResource($contact));
    }

    public function updateContact(Request $request, Carrier $carrier, CarrierContact $contact): JsonResponse
    {
        $validated = $request->validate([
            'type'       => ['sometimes', 'required', 'string', 'in:PHONE,WHATSAPP,EMAIL,OTHER'],
            'value'      => ['sometimes', 'required', 'string', 'max:200'],
            'label'      => ['nullable', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $contact->update($validated);

        return $this->success(new CarrierContactResource($contact->refresh()));
    }

    public function destroyContact(Carrier $carrier, CarrierContact $contact): JsonResponse
    {
        $contact->delete();
        return $this->noContent();
    }
}
