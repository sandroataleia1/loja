<?php

declare(strict_types=1);

namespace App\Core\Auth\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class RegisterDTO extends BaseDTO
{
    public function __construct(
        public readonly string $tenantName,
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $legalName  = null,
        public readonly ?string $document   = null,
        public readonly ?string $phone      = null,
        public readonly string $deviceName  = 'api',
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            tenantName: $request->string('tenant_name')->trim()->toString(),
            name:       $request->string('name')->trim()->toString(),
            email:      $request->string('email')->lower()->trim()->toString(),
            password:   $request->string('password')->toString(),
            legalName:  $request->string('legal_name')->trim()->toString() ?: null,
            document:   $request->string('document')->trim()->toString() ?: null,
            phone:      $request->string('phone')->trim()->toString() ?: null,
            deviceName: $request->string('device_name', $request->userAgent() ?? 'api')->toString(),
        );
    }
}
