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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerSensitiveViewController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function __construct(private readonly AuditLogger $audit) {}

    public function store(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $validated = $request->validate([
            'field' => ['required', 'string', 'in:document,income,credit_score'],
        ]);

        $this->audit->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::Customer,
            entityUuid: $customer->uuid,
            action:     AuditActionEnum::CustomerSensitiveDataViewed,
            tenantId:   TenantContext::getIdOrFail(),
            userId:     auth()->id(),
            metadata:   ['field' => $validated['field']],
            ip:         $request->ip(),
            userAgent:  $request->userAgent(),
        ));

        return $this->noContent();
    }
}
