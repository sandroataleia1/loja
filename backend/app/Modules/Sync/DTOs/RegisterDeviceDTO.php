<?php

declare(strict_types=1);

namespace App\Modules\Sync\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class RegisterDeviceDTO extends BaseDTO
{
    public function __construct(
        public string  $storeId,
        public string  $deviceUuid,
        public string  $name,
        public ?string $platform,
        public ?string $appVersion,
        public ?array  $metadata,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            storeId:    $request->string('store_id')->toString(),
            deviceUuid: $request->string('device_uuid')->toString(),
            name:       $request->string('name')->toString(),
            platform:   $request->string('platform')->value() ?: null,
            appVersion: $request->string('app_version')->value() ?: null,
            metadata:   $request->array('metadata') ?: null,
        );
    }
}
