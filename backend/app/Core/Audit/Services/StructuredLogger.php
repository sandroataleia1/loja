<?php

declare(strict_types=1);

namespace App\Core\Audit\Services;

use App\Core\Tenancy\Services\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class StructuredLogger
{
    public function info(string $message, array $context = []): void
    {
        Log::info($message, $this->enrich($context));
    }

    public function warning(string $message, array $context = []): void
    {
        Log::warning($message, $this->enrich($context));
    }

    public function error(string $message, array $context = []): void
    {
        Log::error($message, $this->enrich($context));
    }

    public function debug(string $message, array $context = []): void
    {
        Log::debug($message, $this->enrich($context));
    }

    private function enrich(array $context): array
    {
        return array_merge([
            'correlation_id' => CorrelationContext::getCorrelationId(),
            'request_id'     => CorrelationContext::getRequestId(),
            'tenant_id'      => TenantContext::getId(),
            'user_id'        => Auth::id(),
        ], $context);
    }
}
