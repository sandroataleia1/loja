<?php

declare(strict_types=1);

namespace App\Modules\Pix\Gateways;

use App\Modules\Pix\Contracts\PixGatewayContract;
use App\Modules\Pix\Data\PixChargeData;

/**
 * Fake PIX gateway for sandbox/development environments.
 * Returns deterministic data so the frontend can test the full QR flow.
 */
final class MockPixGateway implements PixGatewayContract
{
    // Minimal valid 1×1 white PNG in base64 — frontend uses for QR preview.
    private const MOCK_QR_IMAGE = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwADhQGAWjR9awAAAABJRU5ErkJggg==';

    public function createCharge(array $params): PixChargeData
    {
        $externalId  = 'mock_' . uniqid();
        $expiresAt   = now()->addMinutes($params['expires_in_minutes'] ?? 30)->toIso8601String();
        $copyPaste   = '00020101021226580014br.gov.bcb.pix0136' . str_pad('mock-key', 36, '0') .
                       '5204000053039865406' . number_format($params['amount_cents'] / 100, 2, '.', '') .
                       '5802BR5913Loja Exemplo6009SAO PAULO6304ABCD';

        return new PixChargeData(
            externalId:   $externalId,
            qrCodeImage:  self::MOCK_QR_IMAGE,
            pixCopyPaste: $copyPaste,
            expiresAt:    $expiresAt,
            status:       'pending',
            rawResponse:  ['mock' => true, 'params' => $params],
        );
    }

    public function getChargeStatus(string $externalId): array
    {
        // Mock always stays pending — use the "simular" endpoint to mark as paid.
        return ['status' => 'pending', 'paid_at' => null];
    }
}
