<?php

declare(strict_types=1);

namespace App\Modules\Pix\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Tenancy\Scopes\TenantScope;
use App\Modules\Pix\Models\PixCharge;
use App\Modules\Pix\Models\TenantPaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Public webhook endpoint called by Asaas when PIX payment status changes.
 * URL: POST /api/webhooks/pix/{tenantUuid}
 *
 * No auth middleware — validated via webhook_token in Asaas-Access-Token header.
 */
final class PixWebhookController extends Controller
{
    public function handle(Request $request, string $tenantUuid): JsonResponse
    {
        $config = TenantPaymentGateway::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantUuid)
            ->first();

        if (! $config) {
            return response()->json(['ok' => false, 'reason' => 'tenant_not_found'], 404);
        }

        // Validate webhook authenticity via the token Asaas sends in the header.
        $headerToken = $request->header('asaas-access-token');
        if ($headerToken !== $config->webhook_token) {
            Log::warning("PIX webhook token mismatch for tenant {$tenantUuid}");
            return response()->json(['ok' => false, 'reason' => 'invalid_token'], 401);
        }

        $payload    = $request->json()->all();
        $event      = $payload['event'] ?? null;
        $externalId = $payload['payment']['id'] ?? null;

        if (! $externalId) {
            return response()->json(['ok' => true, 'reason' => 'no_payment_id']);
        }

        $charge = PixCharge::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantUuid)
            ->where('external_id', $externalId)
            ->first();

        if (! $charge) {
            return response()->json(['ok' => true, 'reason' => 'charge_not_tracked']);
        }

        $newStatus = match ($event) {
            'PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED' => 'paid',
            'PAYMENT_OVERDUE'                        => 'expired',
            'PAYMENT_REFUNDED'                       => 'refunded',
            default                                  => null,
        };

        if ($newStatus && $charge->status !== $newStatus) {
            $charge->update([
                'status'  => $newStatus,
                'paid_at' => $newStatus === 'paid' ? now() : null,
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
