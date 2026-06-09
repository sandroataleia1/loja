<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Events;

use App\Modules\Catalog\Models\Variant;
use Illuminate\Foundation\Events\Dispatchable;

final class VariantCreated
{
    use Dispatchable;

    public function __construct(public readonly Variant $variant) {}
}
