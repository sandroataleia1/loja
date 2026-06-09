<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\DTOs;

final readonly class PublishProductDTO
{
    public function __construct(
        public readonly string  $tenantId,
        public readonly string  $channelId,
        public readonly string  $productId,
        public readonly ?array  $metadataOverrides = null,  // per-channel copy: title, description, etc.
    ) {}
}
