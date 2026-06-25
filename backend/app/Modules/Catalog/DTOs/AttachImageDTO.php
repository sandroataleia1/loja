<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final readonly class AttachImageDTO extends BaseDTO
{
    public function __construct(
        public string        $imageableId,
        public string        $imageableType,
        public ?string       $url,
        public ?UploadedFile $file,
        public ?string       $thumbnailUrl,
        public ?string       $altText,
        public int           $sortOrder,
        public bool          $isPrimary,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            imageableId:   $request->string('imageable_id')->toString(),
            imageableType: $request->string('imageable_type')->toString(),
            url:           $request->string('url')->value() ?: null,
            file:          $request->hasFile('file') ? $request->file('file') : null,
            thumbnailUrl:  $request->string('thumbnail_url')->value() ?: null,
            altText:       $request->string('alt_text')->value() ?: null,
            sortOrder:     $request->integer('sort_order', 0),
            isPrimary:     $request->boolean('is_primary', false),
        );
    }
}
