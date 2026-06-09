<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\DTOs;

use App\Modules\Fiscal\Enums\FiscalDocumentTypeEnum;
use App\Modules\Fiscal\Enums\FiscalProviderEnum;
use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class RequestFiscalDocumentDTO extends BaseDTO
{
    public function __construct(
        public string                  $storeId,
        public ?string                 $saleId,
        public FiscalDocumentTypeEnum  $type,
        public FiscalProviderEnum      $provider,
        public bool                    $contingencyMode,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            storeId:         $request->string('store_id')->toString(),
            saleId:          $request->string('sale_id')->value() ?: null,
            type:            FiscalDocumentTypeEnum::from($request->string('type')->toString()),
            provider:        FiscalProviderEnum::from($request->string('provider', FiscalProviderEnum::Stub->value)->toString()),
            contingencyMode: $request->boolean('contingency_mode', false),
        );
    }
}
