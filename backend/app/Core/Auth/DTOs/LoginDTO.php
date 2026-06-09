<?php

declare(strict_types=1);

namespace App\Core\Auth\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class LoginDTO extends BaseDTO
{
    public function __construct(
        public readonly string  $email,
        public readonly string  $password,
        public readonly string  $deviceName,
        public readonly ?string $tenantId = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            email:      $request->string('email')->lower()->trim()->toString(),
            password:   $request->string('password')->toString(),
            deviceName: $request->string('device_name', $request->userAgent() ?? 'api')->toString(),
            tenantId:   $request->header('X-Tenant-ID') ?: null,
        );
    }
}
