<?php

declare(strict_types=1);

namespace App\Modules\Customers\Actions;

use App\Core\Audit\DTOs\AuditLogDTO;
use App\Core\Audit\Enums\AuditActionEnum;
use App\Core\Audit\Enums\AuditEntityTypeEnum;
use App\Core\Audit\Services\AuditLogger;
use App\Core\Auth\Models\User;
use App\Modules\Customers\Enums\SpcStatusEnum;
use App\Modules\Customers\Models\Customer;

final readonly class CreditAnalysisAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function execute(Customer $customer, array $data, User $analyst): Customer
    {
        $oldValues = [
            'credit_score'         => $customer->credit_score,
            'spc_status'           => $customer->spc_status?->value,
            'monthly_income'       => $customer->monthly_income,
            'credit_analysis_date' => $customer->credit_analysis_date?->toDateString(),
        ];

        $customer->update([
            'monthly_income'       => $data['monthly_income']       ?? $customer->monthly_income,
            'profession'           => $data['profession']           ?? $customer->profession,
            'employer'             => $data['employer']             ?? $customer->employer,
            'employer_phone'       => $data['employer_phone']       ?? $customer->employer_phone,
            'work_start_date'      => $data['work_start_date']      ?? $customer->work_start_date,
            'credit_score'         => $data['credit_score']         ?? $customer->credit_score,
            'credit_notes'         => $data['credit_notes']         ?? $customer->credit_notes,
            'credit_analysis_date' => now()->toDateString(),
            'credit_analyzed_by'   => $analyst->uuid,
            'spc_status'           => $data['spc_status']           ?? $customer->spc_status,
            'spc_consulted_at'     => isset($data['spc_status']) ? now() : $customer->spc_consulted_at,
        ]);

        $this->auditLogger->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::Customer,
            entityUuid: $customer->uuid,
            action:     AuditActionEnum::CustomerCreditAnalyzed,
            tenantId:   $customer->tenant_id,
            userId:     $analyst->uuid,
            oldValues:  $oldValues,
            newValues:  [
                'credit_score'         => $customer->credit_score,
                'spc_status'           => $customer->spc_status?->value,
                'credit_analysis_date' => $customer->credit_analysis_date?->toDateString(),
            ],
        ));

        return $customer->refresh();
    }
}
