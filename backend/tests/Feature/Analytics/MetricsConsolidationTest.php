<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Analytics\Models\CustomerMetrics;
use App\Modules\Analytics\Models\ProductMetrics;
use App\Modules\Analytics\Services\MetricsCalculator;
use Carbon\Carbon;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    TenantContext::set($this->tenant->uuid);
    $this->calculator = app(MetricsCalculator::class);
});

afterEach(fn () => TenantContext::clear());

// ── MetricsCalculator — pure calculations ─────────────────────────────────────

it('calculates average ticket correctly', function (): void {
    expect($this->calculator->averageTicket(4, 800.00))->toBe(200.00)
        ->and($this->calculator->averageTicket(0, 0))->toBe(0.0);
});

it('calculates return rate capped at 1.0', function (): void {
    expect($this->calculator->returnRate(100, 10))->toBe(0.1)
        ->and($this->calculator->returnRate(100, 200))->toBe(1.0)
        ->and($this->calculator->returnRate(0, 5))->toBe(0.0);
});

it('calculates purchase frequency in days', function (): void {
    $dates = [
        Carbon::parse('2026-01-01'),
        Carbon::parse('2026-01-31'),
        Carbon::parse('2026-03-01'),
    ];

    $freq = $this->calculator->purchaseFrequency($dates);

    // Gap 1: 30 days, Gap 2: 29 days → avg 29.5
    expect($freq)->toBeFloat()->toBeLessThan(31)->toBeGreaterThan(28);
});

it('returns null purchase frequency for a single purchase', function (): void {
    expect($this->calculator->purchaseFrequency([Carbon::now()]))->toBeNull();
});

it('calculates days since last purchase', function (): void {
    $lastPurchase = Carbon::now()->subDays(45);
    $days = $this->calculator->daysSinceLastPurchase($lastPurchase);

    expect($days)->toBe(45);
});

it('returns null days since last purchase when never purchased', function (): void {
    expect($this->calculator->daysSinceLastPurchase(null))->toBeNull();
});

it('converts cents to decimal correctly', function (): void {
    expect($this->calculator->centsToDecimal(19990))->toBe(199.90)
        ->and($this->calculator->centsToDecimal(0))->toBe(0.0)
        ->and($this->calculator->centsToDecimal(1))->toBe(0.01);
});

// ── CustomerMetrics model helpers ─────────────────────────────────────────────

it('identifies dormant customers', function (): void {
    $customerId = Str::uuid()->toString();

    CustomerMetrics::create([
        'tenant_id'                 => $this->tenant->uuid,
        'customer_id'               => $customerId,
        'total_orders'              => 3,
        'total_spent'               => 500,
        'average_ticket'            => 166.67,
        'last_purchase_at'          => now()->subDays(100),
        'days_since_last_purchase'  => 100,
        'computed_at'               => now(),
    ]);

    expect(CustomerMetrics::dormant(90)->count())->toBe(1)
        ->and(CustomerMetrics::dormant(120)->count())->toBe(0);
});

it('identifies active customers', function (): void {
    CustomerMetrics::create([
        'tenant_id'                => $this->tenant->uuid,
        'customer_id'              => Str::uuid()->toString(),
        'total_orders'             => 1,
        'total_spent'              => 100,
        'average_ticket'           => 100,
        'last_purchase_at'         => now()->subDays(10),
        'days_since_last_purchase' => 10,
        'computed_at'              => now(),
    ]);
    CustomerMetrics::create([
        'tenant_id'                => $this->tenant->uuid,
        'customer_id'              => Str::uuid()->toString(),
        'total_orders'             => 1,
        'total_spent'              => 100,
        'average_ticket'           => 100,
        'last_purchase_at'         => now()->subDays(60),
        'days_since_last_purchase' => 60,
        'computed_at'              => now(),
    ]);

    expect(CustomerMetrics::active(30)->count())->toBe(1);
});

// ── ProductMetrics model helpers ──────────────────────────────────────────────

it('identifies stale products', function (): void {
    ProductMetrics::create([
        'tenant_id'        => $this->tenant->uuid,
        'product_id'       => Str::uuid()->toString(),
        'units_sold'       => 5,
        'gross_revenue'    => 500,
        'return_rate'      => 0,
        'stock_turnover'   => 0,
        'days_without_sale' => 45,
        'computed_at'      => now(),
    ]);

    expect(ProductMetrics::stale(30)->count())->toBe(1)
        ->and(ProductMetrics::stale(60)->count())->toBe(0);
});

it('identifies high-return products', function (): void {
    ProductMetrics::create([
        'tenant_id'        => $this->tenant->uuid,
        'product_id'       => Str::uuid()->toString(),
        'units_sold'       => 100,
        'gross_revenue'    => 5000,
        'return_rate'      => 0.12,
        'stock_turnover'   => 0,
        'computed_at'      => now(),
    ]);

    expect(ProductMetrics::highReturn(0.10)->count())->toBe(1)
        ->and(ProductMetrics::highReturn(0.15)->count())->toBe(0);
});
