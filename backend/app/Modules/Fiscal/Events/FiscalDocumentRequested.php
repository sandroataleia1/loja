<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Events;

use App\Modules\Fiscal\Models\FiscalDocument;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FiscalDocumentRequested
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly FiscalDocument $document,
    ) {}
}
