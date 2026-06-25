<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Audit\DTOs\AuditLogDTO;
use App\Core\Audit\Enums\AuditActionEnum;
use App\Core\Audit\Enums\AuditEntityTypeEnum;
use App\Core\Audit\Services\AuditLogger;
use App\Core\Auth\Enums\ApprovalRequiredEnum;
use App\Core\Auth\Enums\ApprovalStatusEnum;
use App\Core\Auth\Http\Resources\ApprovalRequestResource;
use App\Core\Auth\Models\ApprovalRequest;
use App\Core\Auth\Models\User;
use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Workflow genérico de aprovação por PIN.
 *
 * Fluxo:
 * 1. Operação detecta necessidade de aprovação (ex.: desconto acima do limite)
 * 2. Frontend chama POST /approvals para criar solicitação pendente
 * 3. Gestor digita PIN para aprovar ou rejeitar
 * 4. Frontend chama POST /approvals/{uuid}/resolve com PIN e decisão
 * 5. Action original é liberada ou bloqueada conforme resolução
 */
final class ApprovalController extends Controller
{
    use HasApiResponse;

    private const PIN_MAX_ATTEMPTS = 3;
    private const PIN_TTL_SECONDS  = 300; // 5 minutos

    public function __construct(private readonly AuditLogger $auditLogger) {}

    /** Lista solicitações de aprovação do tenant (pendentes por padrão). */
    public function index(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();
        $status   = $request->string('status', 'pending')->toString();

        $requests = ApprovalRequest::forTenant($tenantId)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->with(['requester', 'resolver'])
            ->orderByDesc('requested_at')
            ->paginate(50);

        return $this->success(
            data: ApprovalRequestResource::collection($requests),
            meta: ['total' => $requests->total(), 'per_page' => $requests->perPage()],
        );
    }

    /** Cria uma nova solicitação de aprovação. */
    public function store(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $data = $request->validate([
            'operation_type' => ['required', 'string', 'in:' . implode(',', array_column(ApprovalRequiredEnum::cases(), 'value'))],
            'entity_type'    => ['nullable', 'string', 'max:50'],
            'entity_uuid'    => ['nullable', 'uuid'],
            'context'        => ['nullable', 'array'],
        ]);

        $approval = ApprovalRequest::create([
            'tenant_id'      => $tenantId,
            'operation_type' => $data['operation_type'],
            'entity_type'    => $data['entity_type'] ?? null,
            'entity_uuid'    => $data['entity_uuid'] ?? null,
            'context'        => $data['context'] ?? null,
            'requested_by'   => auth()->id(),
            'requested_at'   => now(),
            'status'         => ApprovalStatusEnum::Pending->value,
        ]);

        return $this->created(
            data: new ApprovalRequestResource($approval->load('requester')),
            message: 'Solicitação de aprovação criada.',
        );
    }

    /** Exibe uma solicitação específica. */
    public function show(string $uuid): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $approval = ApprovalRequest::forTenant($tenantId)
            ->where('uuid', $uuid)
            ->with(['requester', 'resolver'])
            ->firstOrFail();

        return $this->success(new ApprovalRequestResource($approval));
    }

    /**
     * Resolve (aprova ou rejeita) via PIN do gestor.
     *
     * PIN máx. 3 tentativas antes de auto-rejeitar a solicitação.
     * Sucesso e falha são registrados em audit_logs.
     */
    public function resolve(Request $request, string $uuid): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $data = $request->validate([
            'pin'    => ['required', 'string', 'digits_between:4,8'],
            'action' => ['required', 'in:approved,rejected'],
            'notes'  => ['nullable', 'string', 'max:500'],
        ]);

        $approval = ApprovalRequest::forTenant($tenantId)
            ->pending()
            ->where('uuid', $uuid)
            ->firstOrFail();

        // Verifica tentativas de PIN para este approval (Redis, TTL 5min)
        $attemptKey   = "approval_pin_attempts:{$uuid}";
        $pinAttempts  = (int) Cache::get($attemptKey, 0);

        if ($pinAttempts >= self::PIN_MAX_ATTEMPTS) {
            $this->autoRejectByPinExhaustion($approval, $tenantId, $request);
            return $this->error(
                'Solicitação bloqueada: número máximo de tentativas de PIN atingido.',
                403,
            );
        }

        // Identifica o aprovador pelo PIN
        $hashedPin = hash('sha256', config('app.key', '') . $data['pin']);
        $approver  = User::where('tenant_id', $tenantId)
            ->where('pin', $hashedPin)
            ->where('is_active', true)
            ->first();

        if ($approver === null) {
            // Incrementa contador de falhas
            Cache::put($attemptKey, $pinAttempts + 1, self::PIN_TTL_SECONDS);

            $this->auditLogger->record(new AuditLogDTO(
                entityType: AuditEntityTypeEnum::ApprovalRequest,
                entityUuid: $approval->uuid,
                action:     AuditActionEnum::ApprovalPinFailed,
                tenantId:   $tenantId,
                userId:     auth()->id(),
                metadata:   [
                    'attempt'    => $pinAttempts + 1,
                    'max'        => self::PIN_MAX_ATTEMPTS,
                    'approval_uuid' => $uuid,
                ],
                ip:        $request->ip(),
                userAgent: $request->userAgent(),
            ));

            // Auto-rejeita na última tentativa
            if ($pinAttempts + 1 >= self::PIN_MAX_ATTEMPTS) {
                $this->autoRejectByPinExhaustion($approval, $tenantId, $request);
                return $this->error('PIN inválido. Solicitação bloqueada por muitas tentativas.', 403);
            }

            $remaining = self::PIN_MAX_ATTEMPTS - ($pinAttempts + 1);
            return $this->error(
                "PIN inválido. {$remaining} tentativa(s) restante(s).",
                403,
            );
        }

        // PIN válido — limpa contador
        Cache::forget($attemptKey);

        $approval->update([
            'status'           => ApprovalStatusEnum::from($data['action'])->value,
            'resolved_by'      => $approver->uuid,
            'resolved_at'      => now(),
            'resolution_notes' => $data['notes'] ?? null,
        ]);

        $auditAction = $data['action'] === 'approved'
            ? AuditActionEnum::ApprovalApproved
            : AuditActionEnum::ApprovalRejected;

        $this->auditLogger->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::ApprovalRequest,
            entityUuid: $approval->uuid,
            action:     $auditAction,
            tenantId:   $tenantId,
            userId:     $approver->uuid,
            oldValues:  ['status' => 'pending'],
            newValues:  [
                'status'      => $data['action'],
                'resolved_by' => $approver->name,
            ],
            metadata:  ['notes' => $data['notes'] ?? null],
            ip:        $request->ip(),
            userAgent: $request->userAgent(),
        ));

        return $this->success(
            data: new ApprovalRequestResource($approval->fresh()->load(['requester', 'resolver'])),
            message: $data['action'] === 'approved' ? 'Operação aprovada.' : 'Operação rejeitada.',
        );
    }

    /** Cancela uma solicitação pendente (pelo próprio solicitante). */
    public function cancel(string $uuid): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $approval = ApprovalRequest::forTenant($tenantId)
            ->pending()
            ->where('uuid', $uuid)
            ->where('requested_by', auth()->id())
            ->firstOrFail();

        $approval->update(['status' => ApprovalStatusEnum::Expired->value]);

        return $this->success(message: 'Solicitação cancelada.');
    }

    private function autoRejectByPinExhaustion(
        ApprovalRequest $approval,
        string $tenantId,
        Request $request,
    ): void {
        if (! $approval->isPending()) {
            return;
        }

        $approval->update([
            'status'           => ApprovalStatusEnum::Rejected->value,
            'resolved_at'      => now(),
            'resolution_notes' => 'Bloqueado: muitas tentativas de PIN',
        ]);

        $this->auditLogger->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::ApprovalRequest,
            entityUuid: $approval->uuid,
            action:     AuditActionEnum::ApprovalRejected,
            tenantId:   $tenantId,
            userId:     auth()->id(),
            oldValues:  ['status' => 'pending'],
            newValues:  ['status' => 'rejected'],
            metadata:   ['reason' => 'pin_exhaustion'],
            ip:         $request->ip(),
            userAgent:  $request->userAgent(),
        ));
    }
}
