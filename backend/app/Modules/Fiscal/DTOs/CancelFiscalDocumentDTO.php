<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class CancelFiscalDocumentDTO extends BaseDTO
{
    public function __construct(
        public ?string $reason,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            reason: $request->string('reason')->value() ?: null,
        );
    }
}
