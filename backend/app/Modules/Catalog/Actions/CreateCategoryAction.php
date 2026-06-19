<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\DTOs\CreateCategoryDTO;
use App\Modules\Catalog\Models\Category;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Exceptions\NotFoundException;

final readonly class CreateCategoryAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
    ) {}

    public function execute(CreateCategoryDTO $dto): Category
    {
        if (Category::where('slug', $dto->slug)->exists()) {
            throw new ConflictException("Categoria com slug '{$dto->slug}' já existe nesta empresa.");
        }

        if ($dto->parentId !== null && ! Category::where('uuid', $dto->parentId)->exists()) {
            throw new NotFoundException("Categoria pai '{$dto->parentId}' não encontrada.");
        }

        $code = $this->generateCode->execute(
            tenantId: TenantContext::getIdOrFail(),
            entity:   SequenceEntityEnum::Category,
        );

        return Category::create([
            'code'        => $code,
            'parent_id'   => $dto->parentId,
            'name'        => $dto->name,
            'slug'        => $dto->slug,
            'description' => $dto->description,
            'image_url'   => $dto->imageUrl,
            'sort_order'  => $dto->sortOrder,
            'is_active'   => $dto->isActive,
        ]);
    }
}
