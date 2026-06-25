<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'        => $this->uuid,
            'code'        => $this->code,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'logo_url'    => $this->logo_url,
            'website_url' => $this->website_url,
            'is_active'                 => $this->is_active,
            'manufacturer_cnpj'         => $this->manufacturer_cnpj,
            'manufacturer_contact_name' => $this->manufacturer_contact_name,
            'manufacturer_contact_email'=> $this->manufacturer_contact_email,
            'manufacturer_contact_phone'=> $this->manufacturer_contact_phone,
            'created_at'                => $this->created_at?->toISOString(),
            'updated_at'                => $this->updated_at?->toISOString(),
        ];
    }
}
