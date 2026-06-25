<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\DTOs\CreateBrandDTO;
use App\Modules\Catalog\Models\Brand;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use App\Shared\Exceptions\ConflictException;

final readonly class CreateBrandAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
    ) {}

    public function execute(CreateBrandDTO $dto): Brand
    {
        if (Brand::where('slug', $dto->slug)->exists()) {
            throw new ConflictException("Marca com slug '{$dto->slug}' já existe nesta empresa.");
        }

        $code = $this->generateCode->execute(
            tenantId: TenantContext::getIdOrFail(),
            entity:   SequenceEntityEnum::Brand,
        );

        return Brand::create([
            'code'                       => $code,
            'name'                       => $dto->name,
            'slug'                       => $dto->slug,
            'description'                => $dto->description,
            'logo_url'                   => $dto->logoUrl,
            'website_url'                => $dto->websiteUrl,
            'is_active'                  => $dto->isActive,
            'manufacturer_cnpj'          => $dto->manufacturerCnpj,
            'manufacturer_contact_name'  => $dto->manufacturerContactName,
            'manufacturer_contact_email' => $dto->manufacturerContactEmail,
            'manufacturer_contact_phone' => $dto->manufacturerContactPhone,
        ]);
    }
}
