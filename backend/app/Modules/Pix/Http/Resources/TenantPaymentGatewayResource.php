<?php

declare(strict_types=1);

namespace App\Modules\Pix\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Pix\Models\TenantPaymentGateway */
final class TenantPaymentGatewayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'              => $this->uuid,
            'gateway'           => $this->gateway,
            // Mask the API key — only show last 4 chars for verification
            'api_key_masked'    => $this->api_key
                ? str_repeat('*', max(0, strlen($this->api_key) - 4)) . substr($this->api_key, -4)
                : null,
            'has_api_key'       => ! empty($this->api_key),
            'environment'       => $this->environment,
            'is_active'         => $this->is_active,
            'pix_key'           => $this->pix_key,
            'pix_key_type'      => $this->pix_key_type,
        ];
    }
}
