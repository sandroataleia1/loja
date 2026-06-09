<?php

declare(strict_types=1);

namespace App\Modules\Media\Enums;

enum MediaTypeEnum: string
{
    case Image    = 'image';
    case Video    = 'video';
    case Banner   = 'banner';
    case Document = 'document';

    public function label(): string
    {
        return match($this) {
            self::Image    => 'Imagem',
            self::Video    => 'Vídeo',
            self::Banner   => 'Banner',
            self::Document => 'Documento',
        };
    }

    public function allowedMimeTypes(): array
    {
        return match($this) {
            self::Image    => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            self::Video    => ['video/mp4', 'video/webm', 'video/quicktime'],
            self::Banner   => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            self::Document => ['application/pdf', 'application/msword'],
        };
    }
}
