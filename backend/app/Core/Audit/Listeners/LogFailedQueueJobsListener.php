<?php

declare(strict_types=1);

namespace App\Core\Audit\Listeners;

use App\Core\Audit\Services\CorrelationContext;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;

final class LogFailedQueueJobsListener
{
    public function handle(JobFailed $event): void
    {
        Log::error('Queue job failed', [
            'connection'     => $event->connectionName,
            'queue'          => $event->job->getQueue(),
            'job_name'       => $event->job->resolveName(),
            'attempts'       => $event->job->attempts(),
            'exception_class' => $event->exception::class,
            'exception'      => $event->exception->getMessage(),
            'correlation_id' => CorrelationContext::getCorrelationId(),
        ]);
    }
}
