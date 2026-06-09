<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class SyncTagsDTO extends BaseDTO
{
    public function __construct(
        public string  $taggableId,
        public string  $taggableType,
        /** @var string[] tag names */
        public array   $tags,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            taggableId:   $request->string('taggable_id')->toString(),
            taggableType: $request->string('taggable_type')->toString(),
            tags:         $request->array('tags'),
        );
    }
}
