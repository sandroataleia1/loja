<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CustomerCommercialReferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'           => $this->uuid,
            'company_name'   => $this->company_name,
            'contact_person' => $this->contact_person,
            'phone'          => $this->phone,
            'notes'          => $this->notes,
        ];
    }
}
