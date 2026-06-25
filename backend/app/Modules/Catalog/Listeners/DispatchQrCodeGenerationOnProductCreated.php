<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Listeners;

use App\Modules\Catalog\Events\ProductCreated;
use App\Modules\Catalog\Jobs\GenerateProductQrCodeJob;

final class DispatchQrCodeGenerationOnProductCreated
{
    public function handle(ProductCreated $event): void
    {
        GenerateProductQrCodeJob::dispatch($event->product->uuid);
    }
}
