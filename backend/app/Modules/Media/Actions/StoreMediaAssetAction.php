<?php

declare(strict_types=1);

namespace App\Modules\Media\Actions;

use App\Modules\Media\Enums\MediaTypeEnum;
use App\Modules\Media\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

final readonly class StoreMediaAssetAction
{
    public function execute(UploadedFile $file, MediaTypeEnum $type, ?array $metadata = null): MediaAsset
    {
        $path = Storage::disk('public')->put(
            "media/{$type->value}",
            $file,
        );

        return MediaAsset::create([
            'type'          => $type,
            'path'          => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $file->getMimeType(),
            'file_size'     => $file->getSize(),
            'is_active'     => true,
            'uploaded_by'   => Auth::id(),
            'metadata'      => $metadata,
        ]);
    }
}
