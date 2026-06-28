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
use App\Modules\Customers\Models\DeletionRequest;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerDeletionRequestController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function __construct(private readonly AuditLogger $audit) {}

    public function store(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $tenantId = TenantContext::getIdOrFail();

        $deletionRequest = DeletionRequest::create([
            'tenant_id'    => $tenantId,
            'entity_type'  => 'customer',
            'entity_id'    => $customer->uuid,
            'requested_by' => auth()->id(),
            'reason'       => $validated['reason'],
            'status'       => 'pending',
        ]);

        $this->audit->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::Customer,
            entityUuid: $customer->uuid,
            action:     AuditActionEnum::CustomerDeletionRequested,
            tenantId:   $tenantId,
            userId:     auth()->id(),
            metadata:   [
                'deletion_request_uuid' => $deletionRequest->uuid,
                'reason'                => $validated['reason'],
            ],
            ip:        $request->ip(),
            userAgent: $request->userAgent(),
        ));

        return response()->json([
            'success' => true,
            'message' => 'Solicitação de exclusão enviada para revisão administrativa.',
            'data'    => [
                'uuid'   => $deletionRequest->uuid,
                'status' => 'pending',
            ],
        ], 202);
    }
}
