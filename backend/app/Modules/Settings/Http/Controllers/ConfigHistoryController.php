<?php

declare(strict_types=1);

namespace App\Modules\Settings\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Settings\Models\ConfigHistory;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Histórico de alterações de configuração do tenant.
 * Requer permission: settings.history (admin/manager).
 */
final class ConfigHistoryController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();
        $limit    = min((int) $request->integer('limit', 50), 200);

        $query = ConfigHistory::where('tenant_id', $tenantId)
            ->with('changedBy:uuid,name,email')
            ->orderByDesc('changed_at');

        if ($request->filled('section')) {
            $query->where('section', $request->string('section')->toString());
        }

        if ($request->filled('key')) {
            $query->where('key', $request->string('key')->toString());
        }

        $history = $query->paginate($limit);

        return $this->success(
            data: $history->map(fn (ConfigHistory $h) => [
                'uuid'        => $h->uuid,
                'section'     => $h->section,
                'key'         => $h->key,
                'old_value'   => $h->old_value,
                'new_value'   => $h->new_value,
                'changed_by'  => $h->changedBy ? [
                    'uuid'  => $h->changedBy->uuid,
                    'name'  => $h->changedBy->name,
                    'email' => $h->changedBy->email,
                ] : null,
                'changed_at'  => $h->changed_at,
                'ip'          => $h->ip,
            ]),
            meta: [
                'total'    => $history->total(),
                'per_page' => $history->perPage(),
                'page'     => $history->currentPage(),
            ],
        );
    }
}
