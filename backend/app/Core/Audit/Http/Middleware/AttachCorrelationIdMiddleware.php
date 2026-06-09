<?php

declare(strict_types=1);

namespace App\Core\Audit\Http\Middleware;

use App\Core\Audit\Services\CorrelationContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class AttachCorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();
        $requestId     = Str::uuid()->toString();

        CorrelationContext::setCorrelationId($correlationId);
        CorrelationContext::setRequestId($requestId);

        // All log entries within this request will carry these fields automatically
        Log::withContext([
            'correlation_id' => $correlationId,
            'request_id'     => $requestId,
        ]);

        $response = $next($request);

        $response->headers->set('X-Correlation-ID', $correlationId);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
