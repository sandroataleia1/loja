<?php

declare(strict_types=1);

namespace App\Modules\Pix\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pix\Http\Requests\UpdateGatewayConfigRequest;
use App\Modules\Pix\Http\Resources\TenantPaymentGatewayResource;
use App\Modules\Pix\Models\TenantPaymentGateway;
use App\Modules\Pix\Services\PixGatewayResolver;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class TenantGatewayController extends Controller
{
    use HasApiResponse;

    /**
     * GET /api/v1/pix/settings
     * Returns current gateway config (api_key masked).
     */
    public function show(PixGatewayResolver $resolver): JsonResponse
    {
        $config = $resolver->configForCurrentTenant();

        if (! $config) {
            return $this->success(null);
        }

        return $this->success(new TenantPaymentGatewayResource($config));
    }

    /**
     * PUT /api/v1/pix/settings
     * Create or update the gateway config for the current tenant.
     */
    public function update(UpdateGatewayConfigRequest $request): JsonResponse
    {
        $config = TenantPaymentGateway::firstOrNew([]);

        if ($config->webhook_token === null) {
            $config->webhook_token = Str::random(64);
        }

        $data = $request->only(['gateway', 'environment', 'is_active', 'pix_key', 'pix_key_type']);

        // Only overwrite api_key when a new value is explicitly sent (non-empty string)
        if ($request->filled('api_key')) {
            $data['api_key'] = $request->string('api_key')->value();
        }

        $config->fill($data)->save();

        return $this->success(new TenantPaymentGatewayResource($config), 'Configurações salvas com sucesso.');
    }

    /**
     * GET /api/v1/pix/public-info
     * Returns non-sensitive gateway info needed by the PDV frontend:
     * pix_key, pix_key_type, has_qrcode_enabled.
     * Used to auto-fill the PIX key input and decide which modes are available.
     */
    public function publicInfo(PixGatewayResolver $resolver): JsonResponse
    {
        $config = $resolver->configForCurrentTenant();

        return $this->success([
            'pix_key'           => $config?->pix_key,
            'pix_key_type'      => $config?->pix_key_type,
            'has_qrcode_enabled'=> $config?->is_active && ! empty($config?->api_key),
            'environment'       => $config?->environment ?? 'sandbox',
        ]);
    }
}
