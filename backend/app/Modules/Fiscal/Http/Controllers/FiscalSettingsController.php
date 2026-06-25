<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Controllers;

use App\Core\Audit\DTOs\AuditLogDTO;
use App\Core\Audit\Enums\AuditActionEnum;
use App\Core\Audit\Enums\AuditEntityTypeEnum;
use App\Core\Audit\Services\AuditLogger;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Fiscal\DTOs\UpsertFiscalSettingsDTO;
use App\Modules\Fiscal\Enums\FiscalEnvironmentEnum;
use App\Modules\Fiscal\Http\Requests\FiscalEnvironmentChangeRequest;
use App\Modules\Fiscal\Http\Resources\FiscalSettingsResource;
use App\Modules\Fiscal\Models\TenantFiscalSettings;
use App\Modules\Settings\Services\ConfigHistoryService;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

final class FiscalSettingsController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly AuditLogger          $auditLogger,
        private readonly ConfigHistoryService $history,
    ) {}

    public function show(): JsonResponse
    {
        $this->authorize('view', TenantFiscalSettings::class);

        $settings = TenantFiscalSettings::where('tenant_id', TenantContext::getIdOrFail())->first();

        if ($settings === null) {
            return $this->success(null);
        }

        return $this->success(new FiscalSettingsResource($settings));
    }

    public function upsert(FiscalEnvironmentChangeRequest $request): JsonResponse
    {
        $this->authorize('upsert', TenantFiscalSettings::class);

        $tenantId = TenantContext::getIdOrFail();
        $settings = TenantFiscalSettings::firstOrNew(['tenant_id' => $tenantId]);

        $oldEnvironment = $settings->exists ? $settings->environment : null;
        $newEnvRaw      = $request->input('environment', 'homologation');
        $newEnvironment = FiscalEnvironmentEnum::from($newEnvRaw);

        // Proteção: mudança para produção exige PIN válido
        if ($newEnvironment === FiscalEnvironmentEnum::Production
            && $oldEnvironment !== FiscalEnvironmentEnum::Production
        ) {
            $user = auth()->user();
            $pin  = $request->input('pin');

            if ($user->pin === null || ! Hash::check($pin, $user->pin)) {
                return $this->error('PIN inválido para operação sensível.', 403);
            }

            // Audit da troca de ambiente
            $this->auditLogger->record(new AuditLogDTO(
                entityType: AuditEntityTypeEnum::FiscalSettings,
                entityUuid: $settings->uuid ?? $tenantId,
                action:     AuditActionEnum::FiscalEnvironmentChanged,
                tenantId:   $tenantId,
                userId:     auth()->id(),
                oldValues:  ['environment' => $oldEnvironment?->value],
                newValues:  ['environment' => FiscalEnvironmentEnum::Production->value],
                metadata:   ['confirmed_by_pin' => true],
                ip:         $request->ip(),
                userAgent:  $request->userAgent(),
            ));
        }

        $oldSnapshot = $settings->exists ? $settings->attributesToArray() : [];

        $dto = UpsertFiscalSettingsDTO::fromRequest($request);

        $settings->fill([
            'environment'         => $newEnvironment,
            'company_name'        => $dto->companyName,
            'cnpj'                => $dto->cnpj,
            'ie'                  => $dto->ie,
            'crt'                 => $dto->crt,
            'csc'                 => $dto->csc,
            'csc_id'              => $dto->cscId,
            'certificate_path'    => $dto->certificatePath,
            'is_active'           => $dto->isActive,
            'auto_issue_nfce'     => $dto->autoIssueNfce,
            'allow_manual_nfce'   => $dto->allowManualNfce,
            'default_fiscal_mode' => $dto->defaultFiscalMode,
            'nfe_default_email'   => $request->input('nfe_default_email'),
        ]);

        if ($dto->certificatePassword !== null) {
            $settings->certificate_password_encrypted = $dto->certificatePassword;
        }

        $settings->save();

        // Histórico de alterações (excluindo campos sensíveis)
        $newSnapshot = $settings->fresh()
            ->makeHidden(['certificate_password_encrypted'])
            ->attributesToArray();

        $sensitiveKeys = ['certificate_password_encrypted'];
        $filteredOld   = array_diff_key($oldSnapshot, array_flip($sensitiveKeys));
        $filteredNew   = array_diff_key($newSnapshot, array_flip($sensitiveKeys));

        $this->history->record(
            tenantId:  $tenantId,
            section:   'fiscal',
            oldValues: $filteredOld,
            newValues: $filteredNew,
            changedBy: auth()->user(),
            request:   $request,
        );

        return $this->success(new FiscalSettingsResource($settings->refresh()));
    }
}
