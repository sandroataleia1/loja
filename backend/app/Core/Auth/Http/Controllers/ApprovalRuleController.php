<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Enums\ApprovalRequiredEnum;
use App\Core\Auth\Enums\ApprovalThresholdTypeEnum;
use App\Core\Auth\Models\ApprovalRule;
use App\Core\Auth\Services\ApprovalRuleService;
use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Gerenciamento de regras de aprovação configuráveis por tenant.
 * Requer role admin ou manager.
 */
final class ApprovalRuleController extends Controller
{
    use HasApiResponse;

    public function __construct(private readonly ApprovalRuleService $service) {}

    /** Lista todas as regras de aprovação do tenant. */
    public function index(): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $rules = ApprovalRule::where('tenant_id', $tenantId)
            ->with('requiredRole')
            ->get()
            ->map(fn (ApprovalRule $r) => $this->format($r));

        return $this->success(data: $rules);
    }

    /** Cria ou atualiza uma regra para um tipo de operação (upsert). */
    public function upsert(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $data = $request->validate([
            'operation_type'   => [
                'required', 'string',
                Rule::in(array_column(ApprovalRequiredEnum::cases(), 'value')),
            ],
            'threshold_type'   => [
                'required', 'string',
                Rule::in(array_column(ApprovalThresholdTypeEnum::cases(), 'value')),
            ],
            'threshold_value'  => [
                'nullable', 'numeric', 'min:0',
                Rule::requiredIf(fn () => $request->input('threshold_type') !== 'always'),
            ],
            'required_role_id' => ['nullable', 'uuid', 'exists:roles,uuid'],
            'is_active'        => ['sometimes', 'boolean'],
        ]);

        $rule = ApprovalRule::updateOrCreate(
            [
                'tenant_id'      => $tenantId,
                'operation_type' => $data['operation_type'],
            ],
            [
                'threshold_type'   => $data['threshold_type'],
                'threshold_value'  => $data['threshold_value'] ?? null,
                'required_role_id' => $data['required_role_id'] ?? null,
                'is_active'        => $data['is_active'] ?? true,
            ],
        );

        $this->service->invalidate($data['operation_type'], $tenantId);

        return $this->success(
            data: $this->format($rule->load('requiredRole')),
            message: 'Regra de aprovação salva.',
        );
    }

    /** Remove (desativa) uma regra. */
    public function destroy(ApprovalRule $approvalRule): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        if ($approvalRule->tenant_id !== $tenantId) {
            return $this->error('Regra não encontrada.', 404);
        }

        $approvalRule->delete();

        $this->service->invalidate($approvalRule->operation_type, $tenantId);

        return $this->success(message: 'Regra de aprovação removida.');
    }

    private function format(ApprovalRule $rule): array
    {
        return [
            'uuid'             => $rule->uuid,
            'operation_type'   => $rule->operation_type,
            'threshold_type'   => $rule->threshold_type,
            'threshold_value'  => $rule->threshold_value,
            'required_role'    => $rule->requiredRole ? [
                'uuid' => $rule->requiredRole->uuid,
                'name' => $rule->requiredRole->name,
            ] : null,
            'is_active'        => $rule->is_active,
        ];
    }
}
