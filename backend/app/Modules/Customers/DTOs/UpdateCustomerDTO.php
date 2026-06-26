<?php

declare(strict_types=1);

namespace App\Modules\Customers\DTOs;

use App\Modules\Customers\Enums\PersonTypeEnum;
use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class UpdateCustomerDTO extends BaseDTO
{
    public function __construct(
        public readonly ?PersonTypeEnum $personType,
        public readonly ?string         $name,
        public readonly ?string         $tradeName,
        public readonly ?string         $document,
        public readonly ?string         $rg,
        public readonly ?string         $ie,
        public readonly ?string         $im,
        public readonly ?string         $situation,
        public readonly ?float          $creditLimit,
        public readonly ?string         $email,
        public readonly ?string         $birthDate,
        public readonly ?string         $notes,
        public readonly ?bool           $isActive,
        public readonly ?array          $addresses,
        public readonly ?array          $contacts,
        public readonly ?array          $tags,
        public readonly ?string         $civilStatus,
        public readonly ?string         $spouseName,
        public readonly ?string         $spouseDocument,
        public readonly ?string         $spousePhone,
        public readonly ?string         $spouseEmployer,
        public readonly ?float          $spouseIncome,
        public readonly ?string         $guarantorName,
        public readonly ?string         $guarantorDocument,
        public readonly ?string         $guarantorPhone,
        public readonly ?string         $guarantorAddress,
        public readonly ?float          $guarantorIncome,
        public readonly ?array          $commercialReferences,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $personTypeRaw = $request->string('person_type')->toString();

        return new static(
            personType:           $personTypeRaw ? PersonTypeEnum::from($personTypeRaw) : null,
            name:                 $request->has('name') ? $request->string('name')->toString() : null,
            tradeName:            $request->has('trade_name') ? ($request->string('trade_name')->toString() ?: null) : null,
            document:             $request->has('document') ? ($request->filled('document') ? preg_replace('/\D/', '', $request->string('document')->toString()) : null) : null,
            rg:                   $request->has('rg') ? ($request->string('rg')->toString() ?: null) : null,
            ie:                   $request->has('ie') ? ($request->string('ie')->toString() ?: null) : null,
            im:                   $request->has('im') ? ($request->string('im')->toString() ?: null) : null,
            situation:            $request->has('situation') ? ($request->string('situation')->toString() ?: null) : null,
            creditLimit:          $request->has('credit_limit') ? ($request->filled('credit_limit') ? (float) $request->input('credit_limit') : null) : null,
            email:                $request->has('email') ? ($request->string('email')->toString() ?: null) : null,
            birthDate:            $request->has('birth_date') ? ($request->string('birth_date')->toString() ?: null) : null,
            notes:                $request->has('notes') ? ($request->string('notes')->toString() ?: null) : null,
            isActive:             $request->has('is_active') ? $request->boolean('is_active') : null,
            addresses:            $request->has('addresses') ? $request->array('addresses') : null,
            contacts:             $request->has('contacts') ? $request->array('contacts') : null,
            tags:                 $request->has('tags') ? $request->array('tags') : null,
            civilStatus:          $request->has('civil_status') ? ($request->string('civil_status')->toString() ?: null) : null,
            spouseName:           $request->has('spouse_name') ? ($request->string('spouse_name')->toString() ?: null) : null,
            spouseDocument:       $request->has('spouse_document') ? ($request->filled('spouse_document') ? preg_replace('/\D/', '', $request->string('spouse_document')->toString()) : null) : null,
            spousePhone:          $request->has('spouse_phone') ? ($request->string('spouse_phone')->toString() ?: null) : null,
            spouseEmployer:       $request->has('spouse_employer') ? ($request->string('spouse_employer')->toString() ?: null) : null,
            spouseIncome:         $request->has('spouse_income') ? ($request->filled('spouse_income') ? (float) $request->input('spouse_income') : null) : null,
            guarantorName:        $request->has('guarantor_name') ? ($request->string('guarantor_name')->toString() ?: null) : null,
            guarantorDocument:    $request->has('guarantor_document') ? ($request->filled('guarantor_document') ? preg_replace('/\D/', '', $request->string('guarantor_document')->toString()) : null) : null,
            guarantorPhone:       $request->has('guarantor_phone') ? ($request->string('guarantor_phone')->toString() ?: null) : null,
            guarantorAddress:     $request->has('guarantor_address') ? ($request->string('guarantor_address')->toString() ?: null) : null,
            guarantorIncome:      $request->has('guarantor_income') ? ($request->filled('guarantor_income') ? (float) $request->input('guarantor_income') : null) : null,
            commercialReferences: $request->has('commercial_references') ? $request->array('commercial_references') : null,
        );
    }
}
