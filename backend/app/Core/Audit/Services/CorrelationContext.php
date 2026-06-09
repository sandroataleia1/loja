<?php

declare(strict_types=1);

namespace App\Core\Audit\Services;

use Illuminate\Support\Str;

/**
 * Static singleton that carries correlation_id and request_id for the
 * lifetime of a single request or queue job.
 *
 * Set by AttachCorrelationIdMiddleware for HTTP requests.
 * Queue jobs should capture and restore via CorrelationContext::setCorrelationId().
 */
final class CorrelationContext
{
    private static ?string $correlationId = null;
    private static ?string $requestId     = null;

    public static function setCorrelationId(string $id): void
    {
        self::$correlationId = $id;
    }

    public static function setRequestId(string $id): void
    {
        self::$requestId = $id;
    }

    public static function getCorrelationId(): string
    {
        return self::$correlationId ??= Str::uuid()->toString();
    }

    public static function getRequestId(): string
    {
        return self::$requestId ??= Str::uuid()->toString();
    }

    /** Reset for testing — do not call in production code. */
    public static function reset(): void
    {
        self::$correlationId = null;
        self::$requestId     = null;
    }

    public static function toArray(): array
    {
        return [
            'correlation_id' => self::getCorrelationId(),
            'request_id'     => self::getRequestId(),
        ];
    }
}
