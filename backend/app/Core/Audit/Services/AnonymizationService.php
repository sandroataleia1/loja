<?php

declare(strict_types=1);

namespace App\Core\Audit\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * LGPD anonymization stub.
 * Full workflow (consent management, erasure requests) pending LGPD feature.
 */
final class AnonymizationService
{
    /**
     * Anonymize LGPD personal data fields on a model that uses HasPersonalData.
     */
    public function anonymize(Model $model): void
    {
        if (! method_exists($model, 'anonymizableFields')) {
            return;
        }

        $updates = [];

        foreach ($model->anonymizableFields() as $field => $replacement) {
            $updates[$field] = is_callable($replacement) ? $replacement() : $replacement;
        }

        if ($updates !== []) {
            $model->updateQuietly($updates);
        }
    }

    /**
     * Return the LGPD personal data field definitions for a model.
     *
     * @return array<string, string>
     */
    public function personalDataFields(Model $model): array
    {
        if (! method_exists($model, 'personalDataFields')) {
            return [];
        }

        return $model->personalDataFields();
    }

    public function hasPersonalData(Model $model): bool
    {
        return $this->personalDataFields($model) !== [];
    }
}
