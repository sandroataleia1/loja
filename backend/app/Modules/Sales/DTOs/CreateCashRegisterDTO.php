<?php

declare(strict_types=1);

namespace App\Modules\Sales\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class CreateCashRegisterDTO extends BaseDTO
{
    public function __construct(
        public string  $storeId,
        public string  $code,
        public string  $name,
        public bool    $isActive,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            storeId:  $request->string('store_id')->toString(),
            code:     $request->string('code')->toString(),
            name:     $request->string('name')->toString(),
            isActive: $request->boolean('is_active', true),
        );
    }
}
