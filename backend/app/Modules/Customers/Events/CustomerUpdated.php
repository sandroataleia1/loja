<?php

declare(strict_types=1);

namespace App\Modules\Customers\Events;

final readonly class CustomerUpdated
{
    public function __construct(
        public string  $tenantId,
        public string  $customerId,
        public string  $customerName,
        public ?string $actorId,
    ) {}
}
