<?php

declare(strict_types=1);

namespace App\Modules\Finance\Events;

use App\Modules\Finance\Models\FinancialEntry;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FinancialEntryCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly FinancialEntry $entry,
    ) {}
}
