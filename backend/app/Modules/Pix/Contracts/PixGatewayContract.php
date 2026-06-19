<?php

declare(strict_types=1);

namespace App\Modules\Pix\Contracts;

use App\Modules\Pix\Data\PixChargeData;

interface PixGatewayContract
{
    /**
     * Create a PIX charge and return charge data with QR code.
     *
     * @param  array{amount_cents: int, description: string, external_reference: string, expires_in_minutes?: int}  $params
     */
    public function createCharge(array $params): PixChargeData;

    /**
     * Retrieve current status of a PIX charge by external gateway ID.
     *
     * @return array{status: string, paid_at: string|null}
     */
    public function getChargeStatus(string $externalId): array;
}
