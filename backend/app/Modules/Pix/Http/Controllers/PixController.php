<?php

declare(strict_types=1);

namespace App\Modules\Pix\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Auth\Enums\PermissionEnum;
use App\Modules\Pix\Http\Requests\CreatePixChargeRequest;
use App\Modules\Pix\Http\Resources\PixChargeResource;
use App\Modules\Pix\Models\PixCharge;
use App\Modules\Pix\Services\PixGatewayResolver;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;

final class PixController extends Controller
{
    use HasApiResponse;

    public function __construct(private readonly PixGatewayResolver $resolver) {}

    /**
     * POST /api/v1/pix/charges
     * Create a PIX charge via the tenant's configured gateway.
     */
    public function createCharge(CreatePixChargeRequest $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission(PermissionEnum::SalesCreate), 403);

        $gateway = $this->resolver->resolveForCurrentTenant();

        $saleUuid   = $request->string('sale_uuid')->value() ?: null;
        $amountCents = $request->integer('amount_cents');
        $description = $request->string('description')->value() ?: "Cobrança PDV #{$saleUuid}";

        $chargeData = $gateway->createCharge([
            'amount_cents'       => $amountCents,
            'description'        => $description,
            'external_reference' => $saleUuid ?? uniqid('pix_'),
            'expires_in_minutes' => $request->integer('expires_in_minutes', 30),
        ]);

        $charge = PixCharge::create([
            'sale_id'         => $saleUuid,
            'gateway'         => 'asaas',
            'external_id'     => $chargeData->externalId,
            'status'          => $chargeData->status,
            'amount_cents'    => $amountCents,
            'qr_code_image'   => $chargeData->qrCodeImage,
            'pix_copy_paste'  => $chargeData->pixCopyPaste,
            'expires_at'      => $chargeData->expiresAt,
            'gateway_response'=> $chargeData->rawResponse,
        ]);

        return $this->created(new PixChargeResource($charge));
    }

    /**
     * GET /api/v1/pix/charges/{charge}
     * Poll charge status — also syncs status from gateway on each call.
     */
    public function getCharge(PixCharge $charge): JsonResponse
    {
        // Only poll the gateway if charge is still pending
        if ($charge->status === 'pending' && $charge->external_id) {
            try {
                $gateway    = $this->resolver->resolveForCurrentTenant();
                $statusData = $gateway->getChargeStatus($charge->external_id);

                if ($statusData['status'] !== $charge->status) {
                    $charge->update([
                        'status'  => $statusData['status'],
                        'paid_at' => $statusData['paid_at'],
                    ]);
                }
            } catch (\Throwable) {
                // Polling failure is non-fatal — return last known state.
            }
        }

        return $this->success(new PixChargeResource($charge->fresh()));
    }

    /**
     * POST /api/v1/pix/charges/{charge}/simulate-payment
     * Only available in sandbox/mock — marks charge as paid for testing.
     */
    public function simulatePayment(PixCharge $charge): JsonResponse
    {
        abort_if(app()->environment('production'), 403, 'Apenas disponível em sandbox.');

        $charge->update([
            'status'  => 'paid',
            'paid_at' => now()->toIso8601String(),
        ]);

        return $this->success(new PixChargeResource($charge->fresh()), 'Pagamento simulado com sucesso.');
    }
}
