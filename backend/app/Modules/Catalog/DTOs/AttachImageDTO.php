<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class AttachImageDTO extends BaseDTO
{
    public function __construct(
        public string  $imageableId,
        public string  $imageableType,
        public string  $url,
        public ?string $thumbnailUrl,
        public ?string $altText,
        public int     $sortOrder,
        public bool    $isPrimary,
        public ?int    $width,
        public ?int    $height,
        public ?int    $sizeBytes,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            imageableId:   $request->string('imageable_id')->toString(),
            imageableType: $request->string('imageable_type')->toString(),
            url:           $request->string('url')->toString(),
            thumbnailUrl:  $request->string('thumbnail_url')->value() ?: null,
            altText:       $request->string('alt_text')->value() ?: null,
            sortOrder:     $request->integer('sort_order', 0),
            isPrimary:     $request->boolean('is_primary', false),
            width:         $request->has('width') ? $request->integer('width') : null,
            height:        $request->has('height') ? $request->integer('height') : null,
            sizeBytes:     $request->has('size_bytes') ? $request->integer('size_bytes') : null,
        );
    }
}
