<?php

declare(strict_types=1);

namespace App\Modules\Partners\Http\Controllers;

use App\Core\Rules\ValidCpf;
use App\Core\Tenancy\Rules\ValidCnpj;
use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Partners\Actions\CreatePartnerAction;
use App\Modules\Partners\Actions\UpdatePartnerAction;
use App\Modules\Partners\DTOs\PartnerDTO;
use App\Modules\Partners\Http\Resources\PartnerContactResource;
use App\Modules\Partners\Http\Resources\PartnerResource;
use App\Modules\Partners\Models\PartnerContact;
use App\Modules\Partners\Models\PartnerProfessional;
use App\Modules\Partners\Models\PartnerReferral;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PartnerController extends Controller
{
    use HasApiResponse;

    private const TYPES = ['MASON', 'FOREMAN', 'ARCHITECT', 'ENGINEER', 'DESIGNER', 'OTHER'];

    public function index(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $partners = PartnerProfessional::where('tenant_id', $tenantId)
            ->when($request->boolean('active'), fn ($q) => $q->where('is_active', true))
            ->when($request->filled('type'),    fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('q'), fn ($q) => $q->where(
                fn ($sub) => $sub->where('name',     'ilike', "%{$request->q}%")
                                  ->orWhere('document', 'ilike', "%{$request->q}%")
                                  ->orWhere('code',     'ilike', "%{$request->q}%")
            ))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: PartnerResource::collection($partners),
            meta: [
                'current_page' => $partners->currentPage(),
                'per_page'     => $partners->perPage(),
                'total'        => $partners->total(),
                'last_page'    => $partners->lastPage(),
            ],
        );
    }

    public function store(Request $request, CreatePartnerAction $action): JsonResponse
    {
        $isCompany = $request->input('person_type') === 'COMPANY';

        $request->validate([
            'code'                        => ['nullable', 'string', 'max:20'],
            'type'                        => ['required', 'string', 'in:' . implode(',', self::TYPES)],
            'person_type'                 => ['nullable', 'string', 'in:INDIVIDUAL,COMPANY'],
            'name'                        => ['required', 'string', 'max:200'],
            'company_name'                => ['nullable', 'string', 'max:200'],
            'document'                    => ['nullable', 'string', 'max:20', $isCompany ? new ValidCnpj() : new ValidCpf()],
            'email'                       => ['nullable', 'email', 'max:254'],
            'phone'                       => ['nullable', 'string', 'max:30'],
            'whatsapp'                    => ['nullable', 'string', 'max:30'],
            'notes'                       => ['nullable', 'string'],
            'referral_commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $partner = $action->execute(PartnerDTO::fromRequest($request));

        return $this->created(new PartnerResource($partner));
    }

    public function show(PartnerProfessional $partnerProfessional): JsonResponse
    {
        return $this->success(new PartnerResource($partnerProfessional->load('contacts')));
    }

    public function update(Request $request, PartnerProfessional $partnerProfessional, UpdatePartnerAction $action): JsonResponse
    {
        $isCompany = ($request->input('person_type') ?? $partnerProfessional->person_type) === 'COMPANY';

        $request->validate([
            'code'                        => ['nullable', 'string', 'max:20'],
            'type'                        => ['sometimes', 'required', 'string', 'in:' . implode(',', self::TYPES)],
            'person_type'                 => ['nullable', 'string', 'in:INDIVIDUAL,COMPANY'],
            'name'                        => ['sometimes', 'required', 'string', 'max:200'],
            'company_name'                => ['nullable', 'string', 'max:200'],
            'document'                    => ['nullable', 'string', 'max:20', $isCompany ? new ValidCnpj() : new ValidCpf()],
            'email'                       => ['nullable', 'email', 'max:254'],
            'phone'                       => ['nullable', 'string', 'max:30'],
            'whatsapp'                    => ['nullable', 'string', 'max:30'],
            'notes'                       => ['nullable', 'string'],
            'is_active'                   => ['boolean'],
            'referral_commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $partner = $action->execute($partnerProfessional, PartnerDTO::fromRequest($request));

        return $this->success(new PartnerResource($partner->load('contacts')));
    }

    public function destroy(PartnerProfessional $partnerProfessional): JsonResponse
    {
        $partnerProfessional->delete();
        return $this->noContent();
    }

    // ── Contacts ─────────────────────────────────────────────────────────────

    public function storeContact(Request $request, PartnerProfessional $partnerProfessional): JsonResponse
    {
        $validated = $request->validate([
            'type'       => ['required', 'string', 'in:PHONE,WHATSAPP,EMAIL,OTHER'],
            'value'      => ['required', 'string', 'max:200'],
            'label'      => ['nullable', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $contact = PartnerContact::create(array_merge(
            ['partner_id' => $partnerProfessional->uuid],
            $validated,
        ));

        return $this->created(new PartnerContactResource($contact));
    }

    public function destroyContact(PartnerProfessional $partnerProfessional, PartnerContact $contact): JsonResponse
    {
        $contact->delete();
        return $this->noContent();
    }

    // ── Statement ─────────────────────────────────────────────────────────────

    public function statement(Request $request, PartnerProfessional $partnerProfessional): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $year  = (int) ($request->query('year', now()->year));
        $month = $request->query('month');

        $query = PartnerReferral::where('tenant_id', $tenantId)
            ->where('partner_id', $partnerProfessional->uuid)
            ->with('customer:uuid,name,code');

        if ($month) {
            $query->whereYear('referred_at', $year)->whereMonth('referred_at', (int) $month);
        } else {
            $query->whereYear('referred_at', $year);
        }

        $referrals = $query->orderByDesc('referred_at')->get();

        $totalCommission   = $referrals->sum('commission_value');
        $pendingCommission = $referrals->where('commission_status', 'pending')->sum('commission_value');
        $paidCommission    = $referrals->where('commission_status', 'paid')->sum('commission_value');

        return $this->success([
            'partner'     => new PartnerResource($partnerProfessional),
            'period'      => ['year' => $year, 'month' => $month],
            'summary'     => [
                'total_referrals'   => $referrals->count(),
                'total_commission'  => $totalCommission,
                'pending_commission' => $pendingCommission,
                'paid_commission'   => $paidCommission,
            ],
            'referrals'   => $referrals->map(fn ($r) => [
                'uuid'              => $r->uuid,
                'customer'          => $r->customer ? ['uuid' => $r->customer->uuid, 'name' => $r->customer->name, 'code' => $r->customer->code] : null,
                'referred_at'       => $r->referred_at,
                'commission_base'   => $r->commission_base,
                'commission_percent' => $r->commission_percent,
                'commission_value'  => $r->commission_value,
                'commission_status' => $r->commission_status,
                'commission_paid_at' => $r->commission_paid_at,
            ]),
        ]);
    }
}
