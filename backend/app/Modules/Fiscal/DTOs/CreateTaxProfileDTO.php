<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\DTOs;

use App\Modules\Fiscal\Enums\TaxRegimeEnum;
use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class CreateTaxProfileDTO extends BaseDTO
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string       $name,
        public TaxRegimeEnum $regime,
        public array        $metadata,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            name:     $request->string('name')->toString(),
            regime:   TaxRegimeEnum::from($request->string('regime')->toString()),
            metadata: $request->has('metadata') ? (array) $request->input('metadata') : [],
        );
    }
}
