<?php

declare(strict_types=1);

namespace App\Core\Audit\DTOs;

use DateTimeImmutable;

final readonly class DomainEventDTO
{
    public function __construct(
        public readonly string             $eventName,
        public readonly array              $payload,
        public readonly ?string            $tenantId      = null,
        public readonly ?string            $correlationId = null,
        public readonly ?DateTimeImmutable $occurredAt    = null,
    ) {}
}
