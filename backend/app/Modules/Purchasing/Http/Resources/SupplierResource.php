<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                       => $this->uuid,
            'code'                       => $this->code,
            'person_type'                => $this->person_type,
            'name'                       => $this->name,
            'trade_name'                 => $this->trade_name,
            'document'                   => $this->document,
            'ie'                         => $this->ie,
            'im'                         => $this->im,
            'email'                      => $this->email,
            'phone'                      => $this->phone,
            'is_active'                  => $this->is_active,
            'status'                     => $this->status?->value,
            'status_label'               => $this->status?->label(),
            'suspension_reason'          => $this->suspension_reason,
            'suspended_at'               => $this->suspended_at?->toISOString(),
            'notes'                      => $this->notes,
            'performance_score'          => $this->performance_score,
            'avg_delivery_days'          => $this->avg_delivery_days,
            'return_rate'                => $this->return_rate,
            'supply_category'            => $this->supply_category?->value ?? $this->supply_category,
            'default_payment_term_days'  => $this->default_payment_term_days,
            'bank_name'                  => $this->bank_name,
            'bank_agency'                => $this->bank_agency,
            'bank_account'               => $this->bank_account,
            'bank_account_type'          => $this->bank_account_type,
            'bank_pix_key'               => $this->bank_pix_key,
            'addresses'                  => SupplierAddressResource::collection($this->whenLoaded('addresses')),
            'contacts'                   => SupplierContactResource::collection($this->whenLoaded('contacts')),
            'created_at'                 => $this->created_at?->toISOString(),
            'updated_at'                 => $this->updated_at?->toISOString(),
        ];
    }
}
