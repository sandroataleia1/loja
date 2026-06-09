<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Events;

use App\Modules\Fiscal\Enums\FiscalModeEnum;
use App\Modules\Sales\Models\Sale;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FiscalEmissionRequested
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Sale           $sale,
        public readonly FiscalModeEnum $mode,
        public readonly bool           $isAutoTriggered,
    ) {}
}
