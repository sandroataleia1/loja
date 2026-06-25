<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class CreateBrandDTO extends BaseDTO
{
    public function __construct(
        public string  $name,
        public string  $slug,
        public ?string $description,
        public ?string $logoUrl,
        public ?string $websiteUrl,
        public bool    $isActive,
        public ?string $code = null,
        public ?string $manufacturerCnpj         = null,
        public ?string $manufacturerContactName   = null,
        public ?string $manufacturerContactEmail  = null,
        public ?string $manufacturerContactPhone  = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            name:                     $request->string('name')->toString(),
            slug:                     Str::slug($request->string('name')->toString()),
            description:              $request->string('description')->value() ?: null,
            logoUrl:                  $request->string('logo_url')->value() ?: null,
            websiteUrl:               $request->string('website_url')->value() ?: null,
            isActive:                 $request->boolean('is_active', true),
            manufacturerCnpj:         $request->string('manufacturer_cnpj')->value() ?: null,
            manufacturerContactName:  $request->string('manufacturer_contact_name')->value() ?: null,
            manufacturerContactEmail: $request->string('manufacturer_contact_email')->value() ?: null,
            manufacturerContactPhone: $request->string('manufacturer_contact_phone')->value() ?: null,
        );
    }
}
