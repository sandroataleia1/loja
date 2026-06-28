<?php

declare(strict_types=1);

namespace App\Modules\Financial\Jobs;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Customers\Models\Customer;
use App\Modules\Finance\Enums\FinancialInstallmentStatusEnum;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Models\FinancialInstallment;
use App\Modules\Financial\Enums\CollectionActionEnum;
use App\Modules\Financial\Models\CollectionActionLog;
use App\Modules\Financial\Models\CollectionRule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Executa regras de cobrança automática para todos os tenants.
 *
 * Agendado diariamente às 08:00 via routes/console.php.
 * Para cada tenant, busca parcelas vencidas há exatamente `trigger_days` dias
 * e aplica a ação configurada, registrando cada execução em collection_actions_log.
 */
final class ExecuteCollectionRulesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries   = 1;
    public int $timeout = 1800;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        Tenant::query()->each(function (Tenant $tenant): void {
            TenantContext::runFor($tenant->uuid, function () use ($tenant): void {
                $this->processForTenant($tenant->uuid);
            });
        });
    }

    private function processForTenant(string $tenantId): void
    {
        $rules = CollectionRule::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('trigger_days')
            ->get();

        if ($rules->isEmpty()) {
            return;
        }

        $today = now()->startOfDay();

        foreach ($rules as $rule) {
            $targetDate = $today->copy()->subDays($rule->trigger_days)->toDateString();

            $installments = FinancialInstallment::query()
                ->whereDate('due_date', $targetDate)
                ->whereIn('status', [
                    FinancialInstallmentStatusEnum::Pending->value,
                    FinancialInstallmentStatusEnum::Overdue->value,
                ])
                ->whereHas('entry', fn ($q) => $q->where('tenant_id', $tenantId))
                ->with('entry')
                ->get();

            foreach ($installments as $installment) {
                $this->executeRule($rule, $installment, $tenantId);
            }
        }
    }

    private function executeRule(CollectionRule $rule, FinancialInstallment $installment, string $tenantId): void
    {
        $customer = $this->resolveCustomer($installment->entry, $tenantId);
        $status   = 'executed';
        $notes    = null;

        try {
            match ($rule->action_type) {
                CollectionActionEnum::Whatsapp      => $this->sendWhatsapp($rule, $installment, $customer),
                CollectionActionEnum::Email         => $this->sendEmail($rule, $installment, $customer),
                CollectionActionEnum::Sms           => $this->sendSms($rule, $installment, $customer),
                CollectionActionEnum::BlockCustomer => $this->blockCustomer($customer, $installment),
                CollectionActionEnum::NotifySeller  => $this->notifySeller($rule, $installment, $customer, $tenantId),
            };
        } catch (\Throwable $e) {
            $status = 'failed';
            $notes  = $e->getMessage();
            Log::warning('ExecuteCollectionRulesJob: falha ao executar ação', [
                'rule'          => $rule->name,
                'installment'   => $installment->uuid,
                'error'         => $e->getMessage(),
            ]);
        }

        CollectionActionLog::create([
            'tenant_id'          => $tenantId,
            'collection_rule_id' => $rule->uuid,
            'installment_id'     => $installment->uuid,
            'customer_id'        => $customer?->uuid,
            'action_type'        => $rule->action_type->value,
            'status'             => $status,
            'notes'              => $notes,
            'executed_at'        => now(),
        ]);
    }

    private function resolveCustomer(FinancialEntry $entry, string $tenantId): ?Customer
    {
        if ($entry->reference_id === null || $entry->reference_type !== 'sale') {
            return null;
        }

        $customerId = DB::table('sales')
            ->where('uuid', $entry->reference_id)
            ->value('customer_id');

        if (! $customerId) {
            return null;
        }

        return Customer::where('tenant_id', $tenantId)->where('uuid', $customerId)->first();
    }

    private function sendWhatsapp(CollectionRule $rule, FinancialInstallment $installment, ?Customer $customer): void
    {
        if ($customer === null) {
            return;
        }

        $message = $this->interpolate($rule->message_template ?? '', $installment, $customer);
        // Integração futura: WhatsAppDispatcherJob::dispatch($customer, $message);
        Log::info('Collection WhatsApp', ['customer' => $customer->uuid, 'message' => $message]);
    }

    private function sendEmail(CollectionRule $rule, FinancialInstallment $installment, ?Customer $customer): void
    {
        if ($customer === null || ! $customer->email) {
            return;
        }

        $message = $this->interpolate($rule->message_template ?? '', $installment, $customer);
        // Integração futura: SendCollectionEmailJob::dispatch($customer, $message);
        Log::info('Collection email', ['to' => $customer->email, 'message' => $message]);
    }

    private function sendSms(CollectionRule $rule, FinancialInstallment $installment, ?Customer $customer): void
    {
        if ($customer === null) {
            return;
        }

        $message = $this->interpolate($rule->message_template ?? '', $installment, $customer);
        Log::info('Collection SMS', ['customer' => $customer->uuid, 'message' => $message]);
    }

    private function blockCustomer(?Customer $customer, FinancialInstallment $installment): void
    {
        if ($customer === null) {
            return;
        }

        if ($customer->blocked_at === null) {
            $customer->update([
                'blocked_at'     => now(),
                'blocked_reason' => 'Inadimplência automática — parcela vencida em '.
                    $installment->due_date?->format('d/m/Y'),
                'is_active'      => false,
            ]);
        }
    }

    private function notifySeller(CollectionRule $rule, FinancialInstallment $installment, ?Customer $customer, string $tenantId): void
    {
        // Integração futura: buscar vendedor responsável e enviar notificação interna
        Log::info('Collection notify_seller', [
            'tenant_id'   => $tenantId,
            'customer_id' => $customer?->uuid,
            'rule'        => $rule->name,
            'due_date'    => $installment->due_date?->toDateString(),
        ]);
    }

    private function interpolate(string $template, FinancialInstallment $installment, ?Customer $customer): string
    {
        return str_replace(
            ['{cliente}', '{valor}', '{vencimento}'],
            [
                $customer?->name ?? 'Cliente',
                'R$ '.number_format(($installment->amount_cents ?? 0) / 100, 2, ',', '.'),
                $installment->due_date?->format('d/m/Y') ?? '',
            ],
            $template,
        );
    }
}
