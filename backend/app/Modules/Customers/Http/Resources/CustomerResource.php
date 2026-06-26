<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Resources;

use App\Modules\Customers\Http\Resources\CustomerCommercialReferenceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                => $this->uuid,
            'code'                => $this->code,
            'person_type'         => $this->person_type?->value,
            'person_type_label'   => $this->person_type?->label(),
            'name'                => $this->name,
            'trade_name'          => $this->trade_name,
            'document'            => $this->document,
            'rg'                  => $this->rg,
            'ie'                  => $this->ie,
            'im'                  => $this->im,
            'email'               => $this->email,
            'birth_date'          => $this->birth_date?->toDateString(),
            'situation'           => $this->situation,
            'status'              => $this->status?->value,
            'status_label'        => $this->status?->label(),
            'blocked_reason'      => $this->blocked_reason,
            'blocked_at'          => $this->blocked_at?->toISOString(),
            'credit_limit'        => $this->credit_limit,
            'notes'               => $this->notes,
            'civil_status'        => $this->civil_status,
            'spouse_name'         => $this->spouse_name,
            'spouse_document'     => $this->spouse_document,
            'spouse_phone'        => $this->spouse_phone,
            'spouse_employer'     => $this->spouse_employer,
            'spouse_income'       => $this->spouse_income,
            'guarantor_name'      => $this->guarantor_name,
            'guarantor_document'  => $this->guarantor_document,
            'guarantor_phone'     => $this->guarantor_phone,
            'guarantor_address'   => $this->guarantor_address,
            'guarantor_income'    => $this->guarantor_income,
            'is_active'           => $this->is_active,
            'is_default_consumer' => $this->is_default_consumer,
            'seller_id'           => $this->seller_id,
            'carrier_id'          => $this->carrier_id,
            'last_purchase_at'    => $this->last_purchase_at?->toISOString(),
            'total_purchases'     => $this->total_purchases,
            'total_orders'        => $this->total_orders,
            'seller'              => $this->whenLoaded('seller'),
            'carrier'             => $this->whenLoaded('carrier'),
            'addresses'           => CustomerAddressResource::collection($this->whenLoaded('addresses')),
            'contacts'            => CustomerContactResource::collection($this->whenLoaded('contacts')),
            'tags'                => CustomerTagResource::collection($this->whenLoaded('tags')),
            'commercial_references' => CustomerCommercialReferenceResource::collection($this->whenLoaded('commercialReferences')),
            'created_at'          => $this->created_at?->toISOString(),
            'updated_at'          => $this->updated_at?->toISOString(),
        ];
    }
}
