<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Analytics\DTOs\AnalyticsEventDTO;
use App\Modules\Analytics\Enums\AggregateTypeEnum;
use App\Modules\Analytics\Models\AnalyticsEvent;
use App\Modules\Analytics\Services\AnalyticsEventRecorder;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    TenantContext::set($this->tenant->uuid);
    $this->recorder = app(AnalyticsEventRecorder::class);
});

afterEach(fn () => TenantContext::clear());

// ── AnalyticsEventRecorder ────────────────────────────────────────────────────

it('records an analytics event to the event store', function (): void {
    $dto = new AnalyticsEventDTO(
        tenantId:      $this->tenant->uuid,
        eventName:     'product.sold',
        aggregateType: AggregateTypeEnum::Product,
        aggregateUuid: 'product-uuid-1',
        payload:       ['quantity' => 2, 'revenue' => 199.90],
        metadata:      ['store_id' => 'store-uuid-1', 'source' => 'pdv'],
    );

    $event = $this->recorder->record($dto);

    expect($event)->not->toBeNull()
        ->and($event->event_name)->toBe('product.sold')
        ->and($event->aggregate_type)->toBe(AggregateTypeEnum::Product)
        ->and($event->aggregate_uuid)->toBe('product-uuid-1')
        ->and($event->payload['quantity'])->toBe(2)
        ->and($event->metadata['source'])->toBe('pdv');

    $this->assertDatabaseHas('analytics_events', [
        'tenant_id'      => $this->tenant->uuid,
        'event_name'     => 'product.sold',
        'aggregate_uuid' => 'product-uuid-1',
    ]);
});

it('stores occurred_at when explicitly provided', function (): void {
    $occurredAt = new \DateTimeImmutable('2026-01-01 10:00:00');

    $dto = new AnalyticsEventDTO(
        tenantId:      $this->tenant->uuid,
        eventName:     'sale.completed',
        aggregateType: AggregateTypeEnum::Sale,
        aggregateUuid: 'sale-uuid-offline',
        payload:       [],
        occurredAt:    $occurredAt,
    );

    $event = $this->recorder->record($dto);

    expect($event->occurred_at->format('Y-m-d H:i:s'))->toBe('2026-01-01 10:00:00');
});

it('has no updated_at column — append-only', function (): void {
    expect(AnalyticsEvent::UPDATED_AT)->toBeNull();
});

// ── Query helpers ─────────────────────────────────────────────────────────────

it('queries events for a specific aggregate', function (): void {
    $productUuid = 'product-query-test';

    $this->recorder->record(new AnalyticsEventDTO(
        tenantId: $this->tenant->uuid, eventName: 'product.viewed',
        aggregateType: AggregateTypeEnum::Product, aggregateUuid: $productUuid,
        payload: [],
    ));
    $this->recorder->record(new AnalyticsEventDTO(
        tenantId: $this->tenant->uuid, eventName: 'product.sold',
        aggregateType: AggregateTypeEnum::Product, aggregateUuid: $productUuid,
        payload: [],
    ));
    $this->recorder->record(new AnalyticsEventDTO(
        tenantId: $this->tenant->uuid, eventName: 'product.viewed',
        aggregateType: AggregateTypeEnum::Product, aggregateUuid: 'other-product',
        payload: [],
    ));

    $events = AnalyticsEvent::forAggregate(AggregateTypeEnum::Product, $productUuid, $this->tenant->uuid)->get();

    expect($events)->toHaveCount(2)
        ->and($events->pluck('event_name')->all())->toContain('product.viewed', 'product.sold');
});

it('queries events by name across all aggregates', function (): void {
    foreach (['uuid-a', 'uuid-b', 'uuid-c'] as $uuid) {
        $this->recorder->record(new AnalyticsEventDTO(
            tenantId: $this->tenant->uuid, eventName: 'product.sold',
            aggregateType: AggregateTypeEnum::Product, aggregateUuid: $uuid,
            payload: [],
        ));
    }
    $this->recorder->record(new AnalyticsEventDTO(
        tenantId: $this->tenant->uuid, eventName: 'product.viewed',
        aggregateType: AggregateTypeEnum::Product, aggregateUuid: 'uuid-d',
        payload: [],
    ));

    $sold = AnalyticsEvent::forEvent('product.sold', $this->tenant->uuid)->get();
    expect($sold)->toHaveCount(3);
});

it('queries events for a time period', function (): void {
    $jan = new \DateTime('2026-01-15');
    $mar = new \DateTime('2026-03-15');

    AnalyticsEvent::create([
        'tenant_id' => $this->tenant->uuid, 'event_name' => 'sale.completed',
        'aggregate_type' => AggregateTypeEnum::Sale, 'aggregate_uuid' => 'sale-jan',
        'payload' => [], 'occurred_at' => '2026-01-15',
    ]);
    AnalyticsEvent::create([
        'tenant_id' => $this->tenant->uuid, 'event_name' => 'sale.completed',
        'aggregate_type' => AggregateTypeEnum::Sale, 'aggregate_uuid' => 'sale-jul',
        'payload' => [], 'occurred_at' => '2026-07-01',
    ]);

    $events = AnalyticsEvent::forPeriod(
        $this->tenant->uuid,
        new \DateTime('2026-01-01'),
        new \DateTime('2026-03-31'),
    )->get();

    expect($events)->toHaveCount(1)
        ->and($events->first()->aggregate_uuid)->toBe('sale-jan');
});
