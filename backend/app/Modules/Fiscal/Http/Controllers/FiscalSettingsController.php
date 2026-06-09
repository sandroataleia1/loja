<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Fiscal\DTOs\UpsertFiscalSettingsDTO;
use App\Modules\Fiscal\Http\Requests\UpsertFiscalSettingsRequest;
use App\Modules\Fiscal\Http\Resources\FiscalSettingsResource;
use App\Modules\Fiscal\Models\TenantFiscalSettings;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

final class FiscalSettingsController extends Controller
{
    use HasApiResponse;

    public function show(): JsonResponse
    {
        $this->authorize('view', TenantFiscalSettings::class);

        $settings = TenantFiscalSettings::where('tenant_id', TenantContext::getIdOrFail())->first();

        if ($settings === null) {
            return $this->success(null);
        }

        return $this->success(new FiscalSettingsResource($settings));
    }

    public function upsert(UpsertFiscalSettingsRequest $request): JsonResponse
    {
        $this->authorize('upsert', TenantFiscalSettings::class);

        $dto      = UpsertFiscalSettingsDTO::fromRequest($request);
        $tenantId = TenantContext::getIdOrFail();

        $settings = TenantFiscalSettings::firstOrNew(['tenant_id' => $tenantId]);

        $settings->fill([
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
        ]);

        // Só atualiza senha se uma nova foi enviada (não sobrescreve com null)
        if ($dto->certificatePassword !== null) {
            $settings->certificate_password_encrypted = $dto->certificatePassword;
        }

        $settings->save();

        return $this->success(new FiscalSettingsResource($settings->refresh()));
    }
}
