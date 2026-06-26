<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CustomerPurchaseReferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'          => $this->uuid,
            'person_type'   => $this->person_type,
            'company_name'  => $this->company_name,
            'phone'         => $this->phone,
            'monthly_limit' => $this->monthly_limit,
        ];
    }
}
