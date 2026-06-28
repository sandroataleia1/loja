<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Controllers;

use App\Core\Audit\DTOs\AuditLogDTO;
use App\Core\Audit\Enums\AuditActionEnum;
use App\Core\Audit\Enums\AuditEntityTypeEnum;
use App\Core\Audit\Services\AuditLogger;
use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Customers\Models\Customer;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class CustomerDataExportController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(Request $request, Customer $customer): Response
    {
        $this->authorize('view', $customer);

        $customer->loadMissing([
            'addresses', 'contacts', 'tags',
            'commercialReferences', 'documents',
            'interactions', 'cards', 'bankReferences',
            'guarantors', 'assets', 'authorizedBuyers',
        ]);

        $data = [
            'export_date'   => now()->toIso8601String(),
            'exported_by'   => auth()->user()?->name,
            'legal_basis'   => 'Art. 18, II, LGPD — Direito de acesso',
            'customer'      => [
                'uuid'             => $customer->uuid,
                'code'             => $customer->code,
                'person_type'      => $customer->person_type,
                'name'             => $customer->name,
                'trade_name'       => $customer->trade_name,
                'document'         => $customer->document,
                'rg'               => $customer->rg,
                'email'            => $customer->email,
                'birth_date'       => $customer->birth_date?->toDateString(),
                'gender'           => $customer->gender,
                'civil_status'     => $customer->civil_status,
                'nationality'      => $customer->nationality,
                'profession'       => $customer->profession,
                'employer'         => $customer->employer,
                'monthly_income'   => $customer->monthly_income,
                'housing_type'     => $customer->housing_type,
                'credit_limit'     => $customer->credit_limit,
                'credit_score'     => $customer->credit_score,
                'status'           => $customer->status?->value,
                'notes'            => $customer->notes,
                'created_at'       => $customer->created_at?->toIso8601String(),
            ],
            'addresses'      => $customer->addresses?->toArray(),
            'contacts'       => $customer->contacts?->toArray(),
            'documents'      => $customer->documents?->map(fn ($d) => [
                'type'        => $d->document_type,
                'name'        => $d->file_name,
                'uploaded_at' => $d->created_at?->toIso8601String(),
            ])->toArray(),
            'interactions'   => $customer->interactions?->toArray(),
            'bank_references' => $customer->bankReferences?->toArray(),
            'assets'         => $customer->assets?->toArray(),
        ];

        $this->audit->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::Customer,
            entityUuid: $customer->uuid,
            action:     AuditActionEnum::CustomerDataExported,
            tenantId:   TenantContext::getIdOrFail(),
            userId:     auth()->id(),
            ip:         $request->ip(),
            userAgent:  $request->userAgent(),
        ));

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response($json, 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => "attachment; filename=\"cliente-{$customer->code}-lgpd.json\"",
        ]);
    }
}
