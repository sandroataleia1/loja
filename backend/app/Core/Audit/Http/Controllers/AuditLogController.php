<?php

declare(strict_types=1);

namespace App\Core\Audit\Http\Controllers;

use App\Core\Audit\Enums\AuditActionEnum;
use App\Core\Audit\Enums\AuditEntityTypeEnum;
use App\Core\Audit\Http\Resources\AuditLogResource;
use App\Core\Audit\Models\AuditLog;
use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuditLogController extends Controller
{
    use HasApiResponse;

    /**
     * Lista logs de auditoria do tenant com filtros.
     *
     * Filtros disponíveis:
     * - action: slug da ação (ex.: auth.login, rbac.role_assigned)
     * - entity_type: tipo de entidade (ex.: user, sale, role)
     * - entity_uuid: UUID da entidade específica
     * - user_id: UUID do usuário que realizou a ação
     * - date_from: data inicial (YYYY-MM-DD)
     * - date_to: data final (YYYY-MM-DD)
     * - high_risk: filtrar apenas ações de alto risco (true/false)
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $query = AuditLog::where('tenant_id', $tenantId)
            ->orderByDesc('created_at');

        // Filtro por ação
        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }

        // Filtro por tipo de entidade
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->string('entity_type')->toString());
        }

        // Filtro por entidade específica
        if ($request->filled('entity_uuid')) {
            $query->where('entity_uuid', $request->string('entity_uuid')->toString());
        }

        // Filtro por usuário
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->string('user_id')->toString());
        }

        // Filtro por data inicial
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from')->toString());
        }

        // Filtro por data final
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to')->toString());
        }

        // Filtro apenas alto risco
        if ($request->boolean('high_risk')) {
            $highRiskValues = array_map(
                fn (AuditActionEnum $a) => $a->value,
                array_filter(AuditActionEnum::cases(), fn (AuditActionEnum $a) => $a->isHighRisk()),
            );
            $query->whereIn('action', $highRiskValues);
        }

        $logs = $query->paginate(50);

        return $this->success(
            data: AuditLogResource::collection($logs),
            meta: [
                'total'        => $logs->total(),
                'per_page'     => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
            ],
        );
    }

    /** Retorna os enums disponíveis para filtros. */
    public function filters(): JsonResponse
    {
        return $this->success([
            'actions' => array_map(fn (AuditActionEnum $a) => [
                'value'       => $a->value,
                'label'       => $a->label(),
                'is_high_risk' => $a->isHighRisk(),
            ], AuditActionEnum::cases()),
            'entity_types' => array_map(fn (AuditEntityTypeEnum $e) => [
                'value' => $e->value,
                'label' => $e->value,
            ], AuditEntityTypeEnum::cases()),
        ]);
    }
}
