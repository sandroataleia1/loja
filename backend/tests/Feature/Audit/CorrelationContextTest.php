<?php

declare(strict_types=1);

use App\Core\Audit\Services\CorrelationContext;

beforeEach(fn () => CorrelationContext::reset());
afterEach(fn () => CorrelationContext::reset());

it('generates a correlation_id on first access', function (): void {
    $id = CorrelationContext::getCorrelationId();

    expect($id)->toBeString()->not->toBeEmpty();
});

it('returns the same correlation_id on subsequent calls', function (): void {
    $first  = CorrelationContext::getCorrelationId();
    $second = CorrelationContext::getCorrelationId();

    expect($first)->toBe($second);
});

it('uses the explicitly set correlation_id', function (): void {
    $id = 'test-correlation-id-abc123';
    CorrelationContext::setCorrelationId($id);

    expect(CorrelationContext::getCorrelationId())->toBe($id);
});

it('generates a request_id independently from correlation_id', function (): void {
    $correlationId = CorrelationContext::getCorrelationId();
    $requestId     = CorrelationContext::getRequestId();

    expect($correlationId)->not->toBe($requestId);
});

it('resets both ids', function (): void {
    $first = CorrelationContext::getCorrelationId();

    CorrelationContext::reset();

    $second = CorrelationContext::getCorrelationId();

    expect($first)->not->toBe($second);
});

it('includes both ids in toArray output', function (): void {
    $data = CorrelationContext::toArray();

    expect($data)->toHaveKeys(['correlation_id', 'request_id']);
});

it('propagates set correlation_id to toArray', function (): void {
    CorrelationContext::setCorrelationId('propagated-id');

    expect(CorrelationContext::toArray()['correlation_id'])->toBe('propagated-id');
});
