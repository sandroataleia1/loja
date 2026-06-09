<?php

declare(strict_types=1);

namespace App\Modules\Analytics\DTOs;

use App\Modules\Analytics\Enums\AggregateTypeEnum;
use DateTimeImmutable;

final readonly class AnalyticsEventDTO
{
    public function __construct(
        public string            $tenantId,
        public string            $eventName,
        public AggregateTypeEnum $aggregateType,
        public string            $aggregateUuid,
        public array             $payload,
        public array             $metadata         = [],
        public ?string           $correlationId    = null,
        public ?DateTimeImmutable $occurredAt      = null,
    ) {}
}
