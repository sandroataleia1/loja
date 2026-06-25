<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class UpdateBrandDTO extends BaseDTO
{
    public function __construct(
        public ?string $name,
        public ?string $description,
        public ?string $logoUrl,
        public ?string $websiteUrl,
        public ?bool   $isActive,
        public ?string $manufacturerCnpj         = null,
        public ?string $manufacturerContactName   = null,
        public ?string $manufacturerContactEmail  = null,
        public ?string $manufacturerContactPhone  = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            name:                     $request->string('name')->value() ?: null,
            description:              $request->has('description') ? ($request->string('description')->value() ?: null) : null,
            logoUrl:                  $request->has('logo_url') ? ($request->string('logo_url')->value() ?: null) : null,
            websiteUrl:               $request->has('website_url') ? ($request->string('website_url')->value() ?: null) : null,
            isActive:                 $request->has('is_active') ? $request->boolean('is_active') : null,
            manufacturerCnpj:         $request->has('manufacturer_cnpj') ? ($request->string('manufacturer_cnpj')->value() ?: null) : null,
            manufacturerContactName:  $request->has('manufacturer_contact_name') ? ($request->string('manufacturer_contact_name')->value() ?: null) : null,
            manufacturerContactEmail: $request->has('manufacturer_contact_email') ? ($request->string('manufacturer_contact_email')->value() ?: null) : null,
            manufacturerContactPhone: $request->has('manufacturer_contact_phone') ? ($request->string('manufacturer_contact_phone')->value() ?: null) : null,
        );
    }
}
