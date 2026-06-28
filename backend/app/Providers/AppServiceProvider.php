<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Analytics\Listeners\UpdateChannelMetricsOnOrderPlaced;
use App\Modules\Analytics\Listeners\UpdateCustomerMetricsOnSaleCompleted;
use App\Modules\Analytics\Listeners\UpdateProductMetricsOnSaleCompleted;
use App\Modules\Analytics\Listeners\UpdateProductMetricsOnSaleReturned;
use App\Core\Auth\Events\UserRegistered;
use App\Modules\Inventory\Events\StoreCreated;
use App\Modules\Omnichannel\Events\ChannelCreated;
use App\Modules\Omnichannel\Events\OrderPlaced;
use App\Modules\Omnichannel\Http\Policies\ChannelPolicy;
use App\Modules\Omnichannel\Http\Policies\OrderPolicy;
use App\Modules\Omnichannel\Models\Channel;
use App\Modules\Omnichannel\Models\Order as OmnichannelOrder;
use App\Core\Audit\Listeners\AuditRoleChangesListener;
use App\Core\Audit\Listeners\LogFailedQueueJobsListener;
use App\Core\Audit\Listeners\RecordDomainEventsListener;
use App\Core\Auth\Events\RoleAssigned;
use App\Core\Auth\Events\RoleRevoked;
use App\Core\Auth\Events\StoreAccessGranted;
use App\Core\Auth\Events\StoreAccessRevoked;
use App\Core\Auth\Models\User;
use App\Core\Tenancy\Events\TenantCreated;
use App\Modules\Inventory\Events\StockAdjusted;
use Illuminate\Queue\Events\JobFailed;
use App\Modules\Catalog\Events\ProductCreated as CatalogProductCreated;
use App\Modules\Catalog\Listeners\DispatchQrCodeGenerationOnProductCreated;
use App\Modules\Catalog\Models\ProductLot;
use App\Modules\Catalog\Models\ProductPrice;
use App\Modules\Catalog\Observers\ProductLotObserver;
use App\Modules\Catalog\Observers\ProductPriceObserver;
use App\Modules\Catalog\Listeners\UpdateProductAnalyticsOnSaleCompleted;
use App\Modules\Catalog\Listeners\UpdateProductAnalyticsOnSaleReturned;
use App\Modules\Customers\Http\Policies\CustomerPolicy;
use App\Modules\Customers\Listeners\CreateDefaultConsumerOnTenantCreated;
use App\Modules\Customers\Models\Customer;
use App\Modules\Finance\Http\Policies\FinancialAccountPolicy;
use App\Modules\Finance\Http\Policies\FinancialEntryPolicy;
use App\Modules\Finance\Http\Policies\FinancialTransferPolicy;
use App\Modules\Finance\Listeners\CancelFinancialEntriesOnSaleCancelled;
use App\Modules\Finance\Listeners\CreateFinancialEntryOnSaleCompleted;
use App\Modules\Finance\Listeners\CreatePayableOnPurchaseReceived;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Models\FinancialTransfer;
use App\Modules\Fiscal\Events\FiscalDocumentAuthorized;
use App\Modules\Fiscal\Events\FiscalDocumentCancelled;
use App\Modules\Fiscal\Events\FiscalDocumentRejected;
use App\Modules\Fiscal\Http\Policies\FiscalDocumentPolicy;
use App\Modules\Fiscal\Http\Policies\FiscalSettingsPolicy;
use App\Modules\Fiscal\Listeners\RequestFiscalDocumentOnSaleCompleted;
use App\Modules\Fiscal\Models\FiscalDocument;
use App\Modules\Fiscal\Models\TenantFiscalSettings;
use App\Modules\Media\Http\Policies\MediaAssetPolicy;
use App\Modules\Media\Models\MediaAsset;
use App\Core\Auth\Http\Policies\RolePolicy;
use App\Core\Auth\Http\Policies\TenantUserPolicy;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Models\TenantUser;
use App\Modules\Sales\Events\CashRegisterOpened;
use App\Modules\Sales\Events\SaleCancelled;
use App\Modules\Sales\Events\SaleCompleted;
use App\Modules\Purchasing\Events\PurchaseReceived;
use App\Modules\Sales\Events\SaleReturned;
use App\Modules\Sales\Http\Policies\SaleReturnPolicy;
use App\Modules\Sales\Models\SaleReturn;
use App\Modules\Conditional\Http\Policies\ConditionalPolicy;
use App\Modules\Conditional\Models\Conditional;
use App\Modules\Sync\Http\Policies\SyncPolicy;
use App\Modules\Sync\Models\SyncDevice;
use App\Modules\Sales\Http\Policies\CashRegisterPolicy;
use App\Modules\Sales\Http\Policies\CashRegisterSessionPolicy;
use App\Modules\Sales\Listeners\UpdateSaleFiscalStatusListener;
use App\Modules\Sales\Models\CashRegister;
use App\Modules\Sales\Models\CashRegisterSession;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->configureModels();
        $this->configureDatabase();
        $this->configureUrls();
        $this->configureSlowQueryLog();
        $this->configureEventListeners();
        $this->configureRateLimiting();
        $this->configureEmailVerification();
    }

    private function configureModels(): void
    {
        Model::shouldBeStrict(! app()->isProduction());

        // unguard() facilita testes e seeders; em produção os $fillable de cada
        // model definem o que é aceito — não há risco de mass-assignment externo
        // porque os FormRequests validam e whitelist os campos antes de chegar aqui.
        if (! app()->isProduction()) {
            Model::unguard();
        }
    }

    private function configureDatabase(): void
    {
        DB::prohibitDestructiveCommands(app()->isProduction());
    }

    private function configureUrls(): void
    {
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }
    }

    private function configureEventListeners(): void
    {
        // ── Tenant ────────────────────────────────────────────────────────────
        Event::listen(TenantCreated::class, CreateDefaultConsumerOnTenantCreated::class);

        // ── Catalog: QR Code generation ───────────────────────────────────────
        Event::listen(CatalogProductCreated::class, DispatchQrCodeGenerationOnProductCreated::class);

        // ── Catalog: histórico imutável de preços ─────────────────────────────
        ProductPrice::observe(ProductPriceObserver::class);

        // ── Catalog: notificação de lotes com vencimento próximo ──────────────
        ProductLot::observe(ProductLotObserver::class);

        // ── Sale lifecycle ────────────────────────────────────────────────────
        Event::listen(SaleCompleted::class, CreateFinancialEntryOnSaleCompleted::class);
        Event::listen(SaleCompleted::class, RequestFiscalDocumentOnSaleCompleted::class);
        Event::listen(SaleCompleted::class, UpdateProductAnalyticsOnSaleCompleted::class);

        // ── SaleReturn lifecycle ──────────────────────────────────────────────
        Event::listen(SaleReturned::class, UpdateProductAnalyticsOnSaleReturned::class);

        // ── Purchase lifecycle ────────────────────────────────────────────────
        Event::listen(PurchaseReceived::class, CreatePayableOnPurchaseReceived::class);

        // ── Sale cancellation → estorno financeiro ────────────────────────────
        Event::listen(SaleCancelled::class, CancelFinancialEntriesOnSaleCancelled::class);

        // ── Audit: RBAC changes ───────────────────────────────────────────────
        Event::listen(RoleAssigned::class,       [AuditRoleChangesListener::class, 'handleRoleAssigned']);
        Event::listen(RoleRevoked::class,        [AuditRoleChangesListener::class, 'handleRoleRevoked']);
        Event::listen(StoreAccessGranted::class, [AuditRoleChangesListener::class, 'handleStoreAccessGranted']);
        Event::listen(StoreAccessRevoked::class, [AuditRoleChangesListener::class, 'handleStoreAccessRevoked']);

        // ── Domain events (analytics / AI / replay) ───────────────────────────
        Event::listen(SaleCompleted::class,            [RecordDomainEventsListener::class, 'handleSaleCompleted']);
        Event::listen(SaleReturned::class,             [RecordDomainEventsListener::class, 'handleSaleReturned']);
        Event::listen(SaleCancelled::class,            [RecordDomainEventsListener::class, 'handleSaleCancelled']);
        Event::listen(StockAdjusted::class,            [RecordDomainEventsListener::class, 'handleStockAdjusted']);
        Event::listen(FiscalDocumentAuthorized::class, [RecordDomainEventsListener::class, 'handleFiscalDocumentAuthorized']);
        Event::listen(CashRegisterOpened::class,       [RecordDomainEventsListener::class, 'handleCashRegisterOpened']);

        // ── Sprint 1: Bootstrap events (audit / future hooks) ─────────────────
        Event::listen(UserRegistered::class,  fn () => null);   // placeholder — listeners added per sprint
        Event::listen(StoreCreated::class,    fn () => null);
        Event::listen(ChannelCreated::class,  fn () => null);

        // ── Analytics: metric projections ─────────────────────────────────────
        Event::listen(SaleCompleted::class, UpdateCustomerMetricsOnSaleCompleted::class);
        Event::listen(SaleCompleted::class, UpdateProductMetricsOnSaleCompleted::class);
        Event::listen(SaleReturned::class,  UpdateProductMetricsOnSaleReturned::class);
        Event::listen(OrderPlaced::class,   UpdateChannelMetricsOnOrderPlaced::class);

        // ── Queue observability ───────────────────────────────────────────────
        Event::listen(JobFailed::class, LogFailedQueueJobsListener::class);

        // ── Fiscal events → Sales sync ────────────────────────────────────────
        Event::listen(FiscalDocumentAuthorized::class, [UpdateSaleFiscalStatusListener::class, 'handleAuthorized']);
        Event::listen(FiscalDocumentCancelled::class,  [UpdateSaleFiscalStatusListener::class, 'handleCancelled']);
        Event::listen(FiscalDocumentRejected::class,   [UpdateSaleFiscalStatusListener::class, 'handleRejected']);

        // ── Policies ──────────────────────────────────────────────────────────
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(\App\Modules\Catalog\Models\Product::class, \App\Modules\Catalog\Http\Policies\ProductPolicy::class);
        Gate::policy(CashRegister::class, CashRegisterPolicy::class);
        Gate::policy(CashRegisterSession::class, CashRegisterSessionPolicy::class);
        Gate::policy(FinancialAccount::class, FinancialAccountPolicy::class);
        Gate::policy(FinancialEntry::class, FinancialEntryPolicy::class);
        Gate::policy(FinancialTransfer::class, FinancialTransferPolicy::class);
        Gate::policy(FiscalDocument::class, FiscalDocumentPolicy::class);
        Gate::policy(TenantFiscalSettings::class, FiscalSettingsPolicy::class);
        Gate::policy(MediaAsset::class, MediaAssetPolicy::class);
        Gate::policy(SyncDevice::class, SyncPolicy::class);
        Gate::policy(SaleReturn::class, SaleReturnPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(TenantUser::class, TenantUserPolicy::class);
        Gate::policy(Channel::class, ChannelPolicy::class);
        Gate::policy(OmnichannelOrder::class, OrderPolicy::class);
        Gate::policy(Conditional::class, ConditionalPolicy::class);
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('auth.register', fn () => Limit::perMinute(3)->by(request()->ip()));

        // Combina IP + e-mail para bloquear força bruta por conta mesmo trocando de IP
        RateLimiter::for('auth.login', fn () =>
            Limit::perMinute(5)->by(request()->ip() . '|' . strtolower((string) request()->input('email')))
        );

        RateLimiter::for('auth.forgot-password',  fn () => Limit::perMinute(3)->by(request()->ip()));
        RateLimiter::for('auth.reset-password',   fn () => Limit::perMinute(3)->by(request()->ip()));
        RateLimiter::for('auth.platform-login',   fn () => Limit::perMinute(5)->by(request()->ip()));

        // PIN login: 10 tentativas / 5 minutos por [tenant_id + IP]
        RateLimiter::for('pin-login', fn () =>
            Limit::perMinutes(5, 10)->by(
                request()->ip() . '|' . (string) request()->input('tenant_id', '')
            )
        );

        // Aprovação por PIN: 10 tentativas / 5 minutos por [IP + approval UUID]
        RateLimiter::for('resolve-seller-pin', fn () =>
            Limit::perMinutes(5, 10)->by(request()->ip())
        );

        // CEP lookup: 20 requests / minuto por usuário (proxy ViaCEP)
        RateLimiter::for('cep', fn () =>
            Limit::perMinute(20)->by((string) (request()->user()?->uuid ?? request()->ip()))
        );
    }

    private function configureEmailVerification(): void
    {
        // O frontend consome o token e chama POST /api/v1/auth/reset-password diretamente.
        // Não há rota Laravel "password.reset" — a URL aponta para o SPA.
        ResetPassword::createUrlUsing(function (User $notifiable, string $token): string {
            $frontendUrl = config('app.frontend_url', config('app.url'));

            return "{$frontendUrl}/reset-password?token={$token}&email=" . urlencode((string) $notifiable->email);
        });
    }

    private function configureSlowQueryLog(): void
    {
        if (! app()->isProduction()) {
            return;
        }

        $threshold = (int) env('DB_SLOW_QUERY_MS', 1000);

        DB::listen(function (QueryExecuted $event) use ($threshold): void {
            if ($event->time >= $threshold) {
                Log::channel('slow_queries')->warning('Slow query detected', [
                    'sql'      => $event->sql,
                    'bindings' => $event->bindings,
                    'time_ms'  => $event->time,
                ]);
            }
        });
    }
}
