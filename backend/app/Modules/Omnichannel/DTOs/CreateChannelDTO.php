<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\DTOs;

use App\Modules\Omnichannel\Enums\ChannelTypeEnum;

final readonly class CreateChannelDTO
{
    public function __construct(
        public readonly string          $tenantId,
        public readonly string          $name,
        public readonly ChannelTypeEnum $type,
        public readonly bool            $isActive   = true,
        public readonly ?string         $storeId    = null,
        public readonly ?array          $metadata   = null,
    ) {}
}
