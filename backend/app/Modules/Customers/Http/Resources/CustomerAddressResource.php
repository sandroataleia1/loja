<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CustomerAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'        => $this->uuid,
            'customer_id' => $this->customer_id,
            'zipcode'     => $this->zipcode,
            'street'      => $this->street,
            'number'      => $this->number,
            'complement'  => $this->complement,
            'district'    => $this->district,
            'city'        => $this->city,
            'state'       => $this->state,
            'country'     => $this->country,
            'is_default'  => $this->is_default,
        ];
    }
}
