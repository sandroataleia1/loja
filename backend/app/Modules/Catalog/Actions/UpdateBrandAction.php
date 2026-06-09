<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\UpdateBrandDTO;
use App\Modules\Catalog\Models\Brand;
use Illuminate\Support\Str;

final readonly class UpdateBrandAction
{
    public function execute(Brand $brand, UpdateBrandDTO $dto): Brand
    {
        $data = array_filter([
            'name'        => $dto->name,
            'slug'        => $dto->name !== null ? Str::slug($dto->name) : null,
            'description' => $dto->description,
            'logo_url'    => $dto->logoUrl,
            'website_url' => $dto->websiteUrl,
            'is_active'   => $dto->isActive,
        ], fn ($v) => $v !== null);

        $brand->update($data);

        return $brand->refresh();
    }
}
