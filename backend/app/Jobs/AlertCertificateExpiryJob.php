<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Modules\Fiscal\Models\TenantFiscalSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job diário que verifica certificados digitais próximos do vencimento.
 *
 * Janelas de alerta: 30, 15, 7, 1 dia antes.
 * Ao disparar o evento CertificateExpiryWarning, a camada de notificações
 * (futura) pode enviar e-mail/Slack para o tenant.
 */
final class AlertCertificateExpiryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    private const ALERT_DAYS = [30, 15, 7, 1];

    public function __construct()
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        foreach (self::ALERT_DAYS as $days) {
            $targetDate = now()->addDays($days)->toDateString();

            // Varredura cross-tenant (apenas leitura + log): ignora o escopo de
            // tenant explicitamente; cada linha carrega seu próprio tenant_id.
            TenantFiscalSettings::withoutTenantScope()
                ->where('is_active', true)
                ->whereDate('certificate_expires_at', $targetDate)
                ->whereNotNull('certificate_expires_at')
                ->get()
                ->each(function (TenantFiscalSettings $settings) use ($days): void {
                    Log::warning('fiscal.certificate.expiry_warning', [
                        'tenant_id'           => $settings->tenant_id,
                        'expires_at'          => $settings->certificate_expires_at,
                        'days_remaining'      => $days,
                    ]);

                    // TODO: disparar CertificateExpiryWarning event → notification channel
                });
        }
    }
}
