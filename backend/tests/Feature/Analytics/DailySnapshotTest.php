<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Analytics\Enums\MetricNameEnum;
use App\Modules\Analytics\Models\DailyMetricSnapshot;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    TenantContext::set($this->tenant->uuid);
});

afterEach(fn () => TenantContext::clear());

// ── DailyMetricSnapshot::record ───────────────────────────────────────────────

it('creates a snapshot entry for a metric', function (): void {
    $date = Carbon::parse('2026-05-01');

    $snapshot = DailyMetricSnapshot::record(
        $this->tenant->uuid,
        MetricNameEnum::TotalRevenue,
        $date,
        12500.50,
    );

    expect($snapshot->metric_name)->toBe(MetricNameEnum::TotalRevenue)
        ->and($snapshot->metric_date->toDateString())->toBe('2026-05-01')
        ->and((float) $snapshot->metric_value)->toBe(12500.5);

    $this->assertDatabaseHas('daily_metric_snapshots', [
        'tenant_id'   => $this->tenant->uuid,
        'metric_date' => '2026-05-01',
    ]);
});

it('upserts — updates value on second call for same day', function (): void {
    $date = Carbon::parse('2026-05-15');

    DailyMetricSnapshot::record($this->tenant->uuid, MetricNameEnum::TotalOrders, $date, 42.0);
    DailyMetricSnapshot::record($this->tenant->uuid, MetricNameEnum::TotalOrders, $date, 58.0);

    $count = DailyMetricSnapshot::where('tenant_id', $this->tenant->uuid)
        ->where('metric_name', MetricNameEnum::TotalOrders)
        ->where('metric_date', '2026-05-15')
        ->count();

    $latest = DailyMetricSnapshot::where('tenant_id', $this->tenant->uuid)
        ->where('metric_name', MetricNameEnum::TotalOrders)
        ->where('metric_date', '2026-05-15')
        ->first();

    expect($count)->toBe(1)
        ->and((float) $latest->metric_value)->toBe(58.0);
});

it('stores metadata alongside the metric', function (): void {
    $date = Carbon::parse('2026-05-20');

    $snapshot = DailyMetricSnapshot::record(
        $this->tenant->uuid,
        MetricNameEnum::StoreRevenue,
        $date,
        8000.0,
        ['store_id' => 'store-abc-123'],
    );

    expect($snapshot->metadata)->toHaveKey('store_id', 'store-abc-123');
});

it('has no updated_at column — append-only after upsert', function (): void {
    expect(DailyMetricSnapshot::UPDATED_AT)->toBeNull();
});

// ── Query helpers ─────────────────────────────────────────────────────────────

it('queries snapshots for a specific metric over time', function (): void {
    $dates = ['2026-01-01', '2026-02-01', '2026-03-01'];

    foreach ($dates as $d) {
        DailyMetricSnapshot::record(
            $this->tenant->uuid,
            MetricNameEnum::TotalRevenue,
            Carbon::parse($d),
            rand(1000, 5000),
        );
    }

    DailyMetricSnapshot::record(
        $this->tenant->uuid,
        MetricNameEnum::TotalOrders,
        Carbon::parse('2026-01-01'),
        100,
    );

    $series = DailyMetricSnapshot::forMetric(MetricNameEnum::TotalRevenue, $this->tenant->uuid)->get();

    expect($series)->toHaveCount(3)
        ->and($series->first()->metric_date->format('Y-m-d'))->toBe('2026-01-01');
});

it('queries snapshots for a date range', function (): void {
    foreach (['2026-01-01', '2026-06-15', '2026-12-31'] as $d) {
        DailyMetricSnapshot::record(
            $this->tenant->uuid,
            MetricNameEnum::ActiveCustomers,
            Carbon::parse($d),
            rand(10, 100),
        );
    }

    $range = DailyMetricSnapshot::forPeriod(
        $this->tenant->uuid,
        new DateTime('2026-01-01'),
        new DateTime('2026-06-30'),
    )->get();

    expect($range)->toHaveCount(2);
});

it('is isolated per tenant', function (): void {
    $otherTenant = Tenant::factory()->create();
    $date        = Carbon::parse('2026-05-01');

    DailyMetricSnapshot::record($this->tenant->uuid, MetricNameEnum::TotalRevenue, $date, 1000);
    DailyMetricSnapshot::record($otherTenant->uuid, MetricNameEnum::TotalRevenue, $date, 2000);

    $count = DailyMetricSnapshot::where('tenant_id', $this->tenant->uuid)->count();

    expect($count)->toBe(1);
});
