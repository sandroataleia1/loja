<?php

declare(strict_types=1);

namespace App\Modules\Sellers\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SellerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'            => $this->uuid,
            'user_id'         => $this->user_id,
            'user_name'       => $this->whenLoaded('user', fn () => $this->user->name),
            'user_email'      => $this->whenLoaded('user', fn () => $this->user->email),
            'code'            => $this->code,
            'nickname'        => $this->nickname,
            'seller_type'     => $this->seller_type?->value ?? $this->seller_type,
            'commission_rate' => $this->commission_rate,
            'monthly_target'  => $this->monthly_target,
            'supervisor_id'   => $this->supervisor_id,
            'region'          => $this->region,
            'is_active'       => $this->is_active,
            'notes'           => $this->notes,
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
        ];
    }
}
