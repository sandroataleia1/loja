<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Http\Controllers;

use App\Core\Features\FeatureEnum;
use App\Core\Tenancy\Services\FeatureManager;
use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gerencia feature flags do tenant autenticado.
 *
 * GET    /api/v1/tenant/features            → lista todas as features com status
 * PATCH  /api/v1/tenant/features/{feature}  → habilita ou desabilita uma feature
 */
final class TenantFeaturesController extends Controller
{
    use HasApiResponse;

    public function __construct(private readonly FeatureManager $features) {}

    public function index(): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        return $this->success($this->features->listAll($tenantId));
    }

    public function update(Request $request, string $featureSlug): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $feature = FeatureEnum::tryFrom($featureSlug);

        if ($feature === null) {
            return $this->error("Feature desconhecida: {$featureSlug}", [], 422);
        }

        $data = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'config'     => ['nullable', 'array'],
        ]);

        $enabledBy = $request->user()?->uuid;

        if ($data['is_enabled']) {
            $this->features->enable($tenantId, $feature, $data['config'] ?? [], $enabledBy);
        } else {
            $this->features->disable($tenantId, $feature);
        }

        return $this->success([
            'feature'    => $feature->value,
            'label'      => $feature->label(),
            'is_enabled' => $data['is_enabled'],
        ]);
    }
}
