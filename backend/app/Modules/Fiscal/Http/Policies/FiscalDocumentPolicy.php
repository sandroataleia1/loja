<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Fiscal\Models\FiscalDocument;
use App\Modules\Fiscal\Models\TenantFiscalSettings;
use Illuminate\Auth\Access\HandlesAuthorization;

final class FiscalDocumentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::FiscalView);
    }

    public function view(User $user, FiscalDocument $doc): bool
    {
        return $user->hasPermission(PermissionEnum::FiscalView);
    }

    /** Emissão manual de NFC-e: fiscal.issue OU allow_manual_nfce habilitado. */
    public function emitNfce(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::FiscalIssue)
            || $this->tenantAllowsManualNfce();
    }

    /** Emissão de NF-e: apenas fiscal.issue. */
    public function emitNfe(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::FiscalIssue);
    }

    /** Emissão manual via PDV — equivale a emitNfce. */
    public function emitManualFiscal(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::FiscalIssue)
            || $this->tenantAllowsManualNfce();
    }

    /** Cancelamento: fiscal.cancel. */
    public function cancel(User $user, FiscalDocument $doc): bool
    {
        return $user->hasPermission(PermissionEnum::FiscalCancel);
    }

    /** Reprocessamento de rejeitado/erro: fiscal.reprocess. */
    public function retry(User $user, FiscalDocument $doc): bool
    {
        return $user->hasPermission(PermissionEnum::FiscalReprocess);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function tenantAllowsManualNfce(): bool
    {
        $tenantId = TenantContext::getId();
        if ($tenantId === null) {
            return false;
        }

        $settings = TenantFiscalSettings::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        return $settings?->allow_manual_nfce === true;
    }
}
