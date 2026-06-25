<?php

declare(strict_types=1);

namespace App\Modules\Carriers\Actions;

use App\Modules\Carriers\Models\Carrier;

final readonly class UpdateCarrierAction
{
    public function execute(Carrier $carrier, array $data): Carrier
    {
        $carrier->update(array_filter($data, fn ($v) => $v !== null));

        return $carrier->refresh();
    }
}
