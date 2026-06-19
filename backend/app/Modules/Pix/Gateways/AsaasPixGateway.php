<?php

declare(strict_types=1);

namespace App\Modules\Pix\Gateways;

use App\Modules\Pix\Contracts\PixGatewayContract;
use App\Modules\Pix\Data\PixChargeData;
use App\Modules\Pix\Models\TenantPaymentGateway;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class AsaasPixGateway implements PixGatewayContract
{
    public function __construct(
        private readonly TenantPaymentGateway $config,
    ) {}

    public function createCharge(array $params): PixChargeData
    {
        $amountBrl  = round($params['amount_cents'] / 100, 2);
        $dueDate    = now()->addDays(1)->format('Y-m-d');
        $expiresIn  = $params['expires_in_minutes'] ?? 30;

        // Asaas requires a customer — use or create the tenant's default consumer.
        $customerId = $this->resolveDefaultCustomer();

        $paymentResponse = Http::withHeaders($this->headers())
            ->post($this->config->apiBaseUrl() . '/payments', [
                'customer'          => $customerId,
                'billingType'       => 'PIX',
                'value'             => $amountBrl,
                'dueDate'           => $dueDate,
                'description'       => $params['description'],
                'externalReference' => $params['external_reference'],
            ])
            ->throw()
            ->json();

        $externalId = $paymentResponse['id'];

        // Fetch QR code
        $qrResponse = Http::withHeaders($this->headers())
            ->get($this->config->apiBaseUrl() . "/payments/{$externalId}/pixQrCode")
            ->throw()
            ->json();

        $expiresAt = now()->addMinutes($expiresIn)->toIso8601String();
        if (! empty($qrResponse['expirationDate'])) {
            $expiresAt = \Carbon\Carbon::parse($qrResponse['expirationDate'])->toIso8601String();
        }

        return new PixChargeData(
            externalId:   $externalId,
            qrCodeImage:  $qrResponse['encodedImage'] ?? '',
            pixCopyPaste: $qrResponse['payload'] ?? '',
            expiresAt:    $expiresAt,
            status:       $this->mapStatus($paymentResponse['status'] ?? 'PENDING'),
            rawResponse:  $paymentResponse,
        );
    }

    public function getChargeStatus(string $externalId): array
    {
        $response = Http::withHeaders($this->headers())
            ->get($this->config->apiBaseUrl() . "/payments/{$externalId}")
            ->throw()
            ->json();

        $status = $this->mapStatus($response['status'] ?? 'PENDING');
        $paidAt = null;

        if ($status === 'paid' && ! empty($response['paymentDate'])) {
            $paidAt = \Carbon\Carbon::parse($response['paymentDate'])->toIso8601String();
        }

        return ['status' => $status, 'paid_at' => $paidAt];
    }

    private function headers(): array
    {
        return ['access_token' => $this->config->api_key];
    }

    private function mapStatus(string $asaasStatus): string
    {
        return match ($asaasStatus) {
            'RECEIVED', 'CONFIRMED' => 'paid',
            'OVERDUE'               => 'expired',
            'REFUNDED'              => 'refunded',
            default                 => 'pending',
        };
    }

    /**
     * Retrieve or lazily create the default Asaas "Consumidor Final" customer for this tenant.
     * The customer ID is cached in gateway settings to avoid repeated lookups.
     */
    private function resolveDefaultCustomer(): string
    {
        $settings = $this->config->settings ?? [];

        if (! empty($settings['default_customer_id'])) {
            return $settings['default_customer_id'];
        }

        $response = Http::withHeaders($this->headers())
            ->post($this->config->apiBaseUrl() . '/customers', [
                'name'     => 'Consumidor Final',
                'groupName' => 'PDV',
            ])
            ->throw()
            ->json();

        $customerId = $response['id'];

        // Persist so we don't create a new customer on every charge.
        $this->config->settings = array_merge($settings, ['default_customer_id' => $customerId]);
        $this->config->save();

        return $customerId;
    }
}
