<?php

declare(strict_types=1);

namespace App\Modules\Sales\Events;

use App\Modules\Sales\Models\CashRegisterSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CashRegisterClosed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly CashRegisterSession $session,
        public readonly array               $aggregates,
    ) {}
}
