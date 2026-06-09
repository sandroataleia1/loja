<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Data;

use App\Modules\Fiscal\Enums\FiscalDocumentTypeEnum;
use App\Modules\Fiscal\Enums\FiscalEnvironmentEnum;

/**
 * Dados necessários para emitir um documento fiscal.
 *
 * Desacoplado do model FiscalDocument para que o adapter receba apenas
 * o que precisa, sem depender do Eloquent diretamente.
 */
final readonly class FiscalIssueData
{
    public function __construct(
        public string                 $documentUuid,
        public FiscalDocumentTypeEnum $type,
        public FiscalEnvironmentEnum  $environment,
        public int                    $series,
        public int                    $number,
        public ?string                $saleUuid,
        public ?string                $storeUuid,
        /** @var array<int, FiscalItemData> */
        public array                  $items,
        public array                  $metadata = [],
    ) {}
}
