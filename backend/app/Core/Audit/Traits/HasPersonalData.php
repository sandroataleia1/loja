<?php

declare(strict_types=1);

namespace App\Core\Audit\Traits;

/**
 * Marker trait for models that hold LGPD personal data.
 *
 * Implementing classes declare which fields are personal data and
 * how they should be anonymized. Used by AnonymizationService.
 */
trait HasPersonalData
{
    /**
     * Fields that contain personal data and their LGPD category.
     *
     * @return array<string, string>  ['field' => 'lgpd_category']
     */
    abstract public function personalDataFields(): array;

    /**
     * Fields to anonymize and their replacement values or callables.
     *
     * @return array<string, mixed>  ['field' => 'replacement' | callable]
     */
    abstract public function anonymizableFields(): array;
}
