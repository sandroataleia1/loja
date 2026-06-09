<?php

declare(strict_types=1);

use App\Core\Tenancy\Exceptions\TenantContextMissingException;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Analytics\Enums\MetricNameEnum;
use App\Modules\Analytics\Models\DailyMetricSnapshot;

/**
 * Cobertura das correções da Fase 13:
 *  - A2-01: TenantScope falha fechado (lança exceção) sem contexto de tenant.
 *  - A2-02: modelos recém-escopados isolam dados entre tenants.
 *  - runFor: restaura o contexto anterior (seguro em listeners síncronos).
 */
describe('A2-01 — fail closed sem contexto de tenant', function (): void {
    it('lança TenantContextMissingException ao consultar modelo multi-tenant sem contexto', function (): void {
        TenantContext::clear();

        expect(fn (): int => DailyMetricSnapshot::query()->count())
            ->toThrow(TenantContextMissingException::class);
    });

    it('permite consulta cross-tenant explícita via withoutTenantScope mesmo sem contexto', function (): void {
        TenantContext::clear();

        expect(fn (): int => DailyMetricSnapshot::withoutTenantScope()->count())
            ->not->toThrow(TenantContextMissingException::class);
    });
});

describe('A2-02 — isolamento dos modelos recém-escopados', function (): void {
    it('não vaza DailyMetricSnapshot entre tenants', function (): void {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $metric  = MetricNameEnum::cases()[0];

        TenantContext::set($tenantA->uuid);
        DailyMetricSnapshot::record($tenantA->uuid, $metric, now(), 10.0);
        expect(DailyMetricSnapshot::count())->toBe(1);

        TenantContext::set($tenantB->uuid);
        expect(DailyMetricSnapshot::count())->toBe(0);

        TenantContext::clear();
    });
});

describe('TenantContext::runFor', function (): void {
    it('restaura o contexto anterior ao final', function (): void {
        TenantContext::set('tenant-pai');

        TenantContext::runFor('tenant-filho', function (): void {
            expect(TenantContext::getId())->toBe('tenant-filho');
        });

        expect(TenantContext::getId())->toBe('tenant-pai');
        TenantContext::clear();
    });

    it('restaura o contexto anterior mesmo quando o callback lança', function (): void {
        TenantContext::set('tenant-pai');

        try {
            TenantContext::runFor('tenant-filho', function (): void {
                throw new RuntimeException('boom');
            });
        } catch (RuntimeException) {
            // esperado
        }

        expect(TenantContext::getId())->toBe('tenant-pai');
        TenantContext::clear();
    });
});
