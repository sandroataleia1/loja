<?php

declare(strict_types=1);

namespace App\Modules\Partners\DTOs;

use Illuminate\Http\Request;

final readonly class PartnerDTO
{
    public function __construct(
        public string  $type,
        public string  $name,
        public ?string $personType                = null,
        public ?string $companyName               = null,
        public ?string $code                      = null,
        public ?string $document                  = null,
        public ?string $email                     = null,
        public ?string $phone                     = null,
        public ?string $whatsapp                  = null,
        public ?string $notes                     = null,
        public ?bool   $isActive                  = true,
        public ?float  $referralCommissionPercent = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            type:                      $request->string('type')->toString(),
            name:                      $request->string('name')->toString(),
            personType:                $request->input('person_type'),
            companyName:               $request->input('company_name'),
            code:                      $request->input('code'),
            document:                  $request->input('document'),
            email:                     $request->input('email'),
            phone:                     $request->input('phone'),
            whatsapp:                  $request->input('whatsapp'),
            notes:                     $request->input('notes'),
            isActive:                  $request->boolean('is_active', true),
            referralCommissionPercent: $request->input('referral_commission_percent') !== null
                ? (float) $request->input('referral_commission_percent')
                : null,
        );
    }
}
