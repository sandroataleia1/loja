<?php

declare(strict_types=1);

namespace App\Core\Audit\Observers;

use App\Core\Audit\Services\StructuredLogger;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Technical model-level audit observer.
 *
 * Writes to the default log channel (not DB) — lightweight tracking of
 * model lifecycle events (created/updated/deleted).
 *
 * Business audit actions (cancel sale, assign role, etc.) are recorded
 * explicitly by AuditLogger with full context.
 */
final class AuditObserver
{
    public function __construct(
        private readonly StructuredLogger $logger,
    ) {}

    public function created(Model $model): void
    {
        $this->log('model.created', $model, [], $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->log('model.updated', $model, $model->getOriginal(), $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->log('model.deleted', $model, $model->getAttributes(), []);
    }

    private function log(string $event, Model $model, array $oldValues, array $newValues): void
    {
        try {
            $this->logger->debug($event, [
                'model'      => class_basename($model),
                'model_key'  => $model->getKey(),
                'old_values' => $this->filterSensitive($model, $oldValues),
                'new_values' => $this->filterSensitive($model, $newValues),
            ]);
        } catch (Throwable) {
            // Observer failures must never propagate
        }
    }

    private function filterSensitive(Model $model, array $attributes): array
    {
        /** @var array<string> $exclude */
        $exclude = property_exists($model, 'auditExclude') ? $model->auditExclude : [];

        if ($exclude === []) {
            return $attributes;
        }

        return array_diff_key($attributes, array_flip($exclude));
    }
}
