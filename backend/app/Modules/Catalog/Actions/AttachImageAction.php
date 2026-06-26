<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\DTOs\AttachImageDTO;
use App\Modules\Catalog\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class AttachImageAction
{
    public function execute(AttachImageDTO $dto): ProductImage
    {
        return DB::transaction(function () use ($dto): ProductImage {
            if ($dto->isPrimary) {
                ProductImage::where('imageable_id', $dto->imageableId)
                    ->where('imageable_type', $dto->imageableType)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            [$url, $thumbnailUrl, $width, $height, $sizeBytes] = $dto->file !== null
                ? $this->handleUpload($dto->file)
                : [$dto->url, $dto->thumbnailUrl, null, null, null];

            return ProductImage::create([
                'tenant_id'      => TenantContext::getIdOrFail(),
                'imageable_id'   => $dto->imageableId,
                'imageable_type' => $dto->imageableType,
                'url'            => $url,
                'thumbnail_url'  => $thumbnailUrl,
                'alt_text'       => $dto->altText,
                'sort_order'     => $dto->sortOrder,
                'is_primary'     => $dto->isPrimary,
                'width'          => $width,
                'height'         => $height,
                'size_bytes'     => $sizeBytes,
            ]);
        });
    }

    /** @return array{string, ?string, ?int, ?int, ?int} [url, thumbnail_url, width, height, size_bytes] */
    private function handleUpload(UploadedFile $file): array
    {
        $tenantId  = TenantContext::getIdOrFail();
        $filename  = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $directory = "catalog/{$tenantId}";

        $path = Storage::disk('public')->putFileAs($directory, $file, $filename);
        $url  = Storage::disk('public')->url($path);

        $sizeBytes    = $file->getSize() ?: null;
        $thumbnailUrl = null;
        $width        = null;
        $height       = null;

        // Tenta ler dimensões e gerar thumbnail 300×300 via GD (sem dependência externa)
        if (extension_loaded('gd')) {
            [$imgW, $imgH] = @getimagesize($file->getRealPath()) ?: [null, null];
            $width  = is_int($imgW) ? $imgW : null;
            $height = is_int($imgH) ? $imgH : null;

            $thumbPath = $this->generateThumbnail($file, $directory, $filename, $tenantId);
            if ($thumbPath !== null) {
                $thumbnailUrl = Storage::disk('public')->url($thumbPath);
            }
        }

        return [$url, $thumbnailUrl, $width, $height, $sizeBytes];
    }

    private function generateThumbnail(
        UploadedFile $file,
        string       $directory,
        string       $filename,
        string       $tenantId,
    ): ?string {
        $mime = $file->getMimeType();
        $src  = match (true) {
            str_contains((string) $mime, 'jpeg') => @imagecreatefromjpeg($file->getRealPath()),
            str_contains((string) $mime, 'png')  => @imagecreatefrompng($file->getRealPath()),
            str_contains((string) $mime, 'webp') => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file->getRealPath()) : false,
            str_contains((string) $mime, 'gif')  => @imagecreatefromgif($file->getRealPath()),
            default                               => false,
        };

        if ($src === false || $src === null) {
            return null;
        }

        $origW = imagesx($src);
        $origH = imagesy($src);
        $size  = 300;
        $ratio = min($size / $origW, $size / $origH);
        $newW  = (int) round($origW * $ratio);
        $newH  = (int) round($origH * $ratio);

        $thumb = imagecreatetruecolor($newW, $newH);
        if ($thumb === false) {
            imagedestroy($src);
            return null;
        }

        imagecopyresampled($thumb, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);

        $tmpFile = tempnam(sys_get_temp_dir(), 'catalog_thumb_');
        imagejpeg($thumb, $tmpFile, 85);
        imagedestroy($thumb);

        $thumbFilename = 'thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
        $thumbPath     = Storage::disk('public')->putFileAs(
            "{$directory}/thumbs",
            new \Illuminate\Http\File($tmpFile),
            $thumbFilename,
        );

        @unlink($tmpFile);

        return $thumbPath ?: null;
    }
}
