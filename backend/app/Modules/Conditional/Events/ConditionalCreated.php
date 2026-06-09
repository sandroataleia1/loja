<?php

declare(strict_types=1);

namespace App\Modules\Conditional\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class ConditionalCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string  $tenantId,
        public readonly string  $conditionalId,
        public readonly ?string $actorId,
    ) {}
}
