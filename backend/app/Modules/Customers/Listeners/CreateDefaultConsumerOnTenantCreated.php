<?php

declare(strict_types=1);

namespace App\Modules\Customers\Listeners;

use App\Core\Tenancy\Events\TenantCreated;
use App\Modules\Customers\Actions\CreateDefaultConsumerAction;

final readonly class CreateDefaultConsumerOnTenantCreated
{
    public function __construct(
        private CreateDefaultConsumerAction $action,
    ) {}

    public function handle(TenantCreated $event): void
    {
        $this->action->execute($event->tenant->uuid);
    }
}
