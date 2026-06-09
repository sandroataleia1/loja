<?php

declare(strict_types=1);

use App\Core\Audit\DTOs\AuditLogDTO;
use App\Core\Audit\Enums\AuditActionEnum;
use App\Core\Audit\Enums\AuditEntityTypeEnum;
use App\Core\Audit\Models\AuditLog;
use App\Core\Audit\Models\DomainEventLog;
use App\Core\Audit\Services\AuditLogger;
use App\Core\Audit\Services\CorrelationContext;
use App\Core\Audit\Services\DomainEventLogger;
use App\Core\Audit\DTOs\DomainEventDTO;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    TenantContext::set($this->tenant->uuid);
    CorrelationContext::reset();
});

afterEach(function (): void {
    TenantContext::clear();
    CorrelationContext::reset();
});

// ── AuditLogger ───────────────────────────────────────────────────────────────

it('records an audit entry in the database', function (): void {
    $entityUuid = Str::uuid()->toString();

    app(AuditLogger::class)->record(new AuditLogDTO(
        entityType: AuditEntityTypeEnum::Sale,
        entityUuid: $entityUuid,
        action:     AuditActionEnum::CancelSale,
        tenantId:   $this->tenant->uuid,
        userId:     Str::uuid()->toString(),
        metadata:   ['reason' => 'customer request'],
        ip:         '127.0.0.1',
    ));

    $log = AuditLog::where('entity_uuid', $entityUuid)->firstOrFail();

    expect($log->entity_type)->toBe(AuditEntityTypeEnum::Sale)
        ->and($log->action)->toBe(AuditActionEnum::CancelSale)
        ->and($log->tenant_id)->toBe($this->tenant->uuid)
        ->and($log->metadata)->toBe(['reason' => 'customer request'])
        ->and($log->ip)->toBe('127.0.0.1');
});

it('auto-populates correlation_id when not provided', function (): void {
    $entityUuid = Str::uuid()->toString();
    $correlationId = CorrelationContext::getCorrelationId();

    app(AuditLogger::class)->record(new AuditLogDTO(
        entityType: AuditEntityTypeEnum::Sale,
        entityUuid: $entityUuid,
        action:     AuditActionEnum::CompleteSale,
    ));

    $log = AuditLog::where('entity_uuid', $entityUuid)->firstOrFail();

    expect($log->correlation_id)->toBe($correlationId);
});

it('uses explicit correlation_id when provided', function (): void {
    $entityUuid    = Str::uuid()->toString();
    $correlationId = Str::uuid()->toString();

    app(AuditLogger::class)->record(new AuditLogDTO(
        entityType:    AuditEntityTypeEnum::User,
        entityUuid:    $entityUuid,
        action:        AuditActionEnum::Login,
        correlationId: $correlationId,
    ));

    $log = AuditLog::where('entity_uuid', $entityUuid)->firstOrFail();

    expect($log->correlation_id)->toBe($correlationId);
});

it('does not have updated_at column', function (): void {
    expect(AuditLog::UPDATED_AT)->toBeNull();
});

// ── AuditLog query helpers ────────────────────────────────────────────────────

it('queries audit entries for a specific entity', function (): void {
    $uuid = Str::uuid()->toString();

    app(AuditLogger::class)->record(new AuditLogDTO(
        entityType: AuditEntityTypeEnum::Sale,
        entityUuid: $uuid,
        action:     AuditActionEnum::CompleteSale,
    ));

    app(AuditLogger::class)->record(new AuditLogDTO(
        entityType: AuditEntityTypeEnum::Sale,
        entityUuid: $uuid,
        action:     AuditActionEnum::CancelSale,
    ));

    $logs = AuditLog::forEntity(AuditEntityTypeEnum::Sale, $uuid)->get();

    expect($logs)->toHaveCount(2);
});

it('queries audit entries for a specific user', function (): void {
    $userId     = Str::uuid()->toString();
    $entityUuid = Str::uuid()->toString();

    app(AuditLogger::class)->record(new AuditLogDTO(
        entityType: AuditEntityTypeEnum::User,
        entityUuid: $entityUuid,
        action:     AuditActionEnum::Login,
        tenantId:   $this->tenant->uuid,
        userId:     $userId,
    ));

    $logs = AuditLog::forUser($userId, $this->tenant->uuid)->get();

    expect($logs)->toHaveCount(1)
        ->and($logs->first()->user_id)->toBe($userId);
});

// ── DomainEventLogger ─────────────────────────────────────────────────────────

it('records a domain event in the database', function (): void {
    app(DomainEventLogger::class)->record(new DomainEventDTO(
        eventName: 'SaleCompleted',
        payload:   ['sale_uuid' => Str::uuid()->toString(), 'total' => 150.00],
        tenantId:  $this->tenant->uuid,
    ));

    $event = DomainEventLog::where('event_name', 'SaleCompleted')
        ->where('tenant_id', $this->tenant->uuid)
        ->firstOrFail();

    expect($event->payload)->toHaveKey('total', 150.00)
        ->and($event->occurred_at)->not->toBeNull()
        ->and($event->created_at)->not->toBeNull();
});

it('uses explicit occurred_at timestamp when provided', function (): void {
    $occurredAt = new DateTimeImmutable('2025-01-15 10:00:00');

    app(DomainEventLogger::class)->record(new DomainEventDTO(
        eventName:  'SaleCompleted',
        payload:    ['sale_uuid' => Str::uuid()->toString()],
        tenantId:   $this->tenant->uuid,
        occurredAt: $occurredAt,
    ));

    $event = DomainEventLog::where('event_name', 'SaleCompleted')
        ->where('tenant_id', $this->tenant->uuid)
        ->firstOrFail();

    expect($event->occurred_at->format('Y-m-d H:i:s'))->toBe('2025-01-15 10:00:00');
});

it('does not have updated_at column on domain event log', function (): void {
    expect(DomainEventLog::UPDATED_AT)->toBeNull();
});

// ── AuditActionEnum ───────────────────────────────────────────────────────────

it('identifies high-risk audit actions', function (): void {
    expect(AuditActionEnum::CancelSale->isHighRisk())->toBeTrue()
        ->and(AuditActionEnum::ReverseFinancialEntry->isHighRisk())->toBeTrue()
        ->and(AuditActionEnum::FailedLogin->isHighRisk())->toBeTrue()
        ->and(AuditActionEnum::CompleteSale->isHighRisk())->toBeFalse()
        ->and(AuditActionEnum::Login->isHighRisk())->toBeFalse();
});
