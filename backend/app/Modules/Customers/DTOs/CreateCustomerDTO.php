<?php

declare(strict_types=1);

namespace App\Modules\Customers\DTOs;

use App\Modules\Customers\Enums\PersonTypeEnum;
use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class CreateCustomerDTO extends BaseDTO
{
    public function __construct(
        public readonly PersonTypeEnum $personType,
        public readonly string         $name,
        public readonly ?string        $tradeName,
        public readonly ?string        $document,
        public readonly ?string        $rg,
        public readonly ?string        $ie,
        public readonly ?string        $im,
        public readonly ?string        $situation,
        public readonly ?float         $creditLimit,
        public readonly ?string        $email,
        public readonly ?string        $birthDate,
        public readonly ?string        $notes,
        public readonly array          $addresses,
        public readonly array          $contacts,
        public readonly array          $tags,
        public readonly ?string        $civilStatus,
        public readonly ?string        $spouseName,
        public readonly ?string        $spouseDocument,
        public readonly ?string        $spousePhone,
        public readonly ?string        $spouseEmployer,
        public readonly ?float         $spouseIncome,
        public readonly ?string        $guarantorName,
        public readonly ?string        $guarantorDocument,
        public readonly ?string        $guarantorPhone,
        public readonly ?string        $guarantorAddress,
        public readonly ?float         $guarantorIncome,
        public readonly array          $commercialReferences,
        public readonly ?string        $spouseProfession,
        public readonly ?string        $spouseBirthDate,
        public readonly ?string        $spouseGender,
        public readonly ?string        $guarantorProfession,
        public readonly ?string        $guarantorBirthDate,
        public readonly ?string        $guarantorGender,
        public readonly array          $purchaseReferences,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            personType:           PersonTypeEnum::from($request->string('person_type')->toString()),
            name:                 $request->string('name')->toString(),
            tradeName:            $request->string('trade_name')->toString() ?: null,
            document:             $request->filled('document') ? preg_replace('/\D/', '', $request->string('document')->toString()) : null,
            rg:                   $request->string('rg')->toString() ?: null,
            ie:                   $request->string('ie')->toString() ?: null,
            im:                   $request->string('im')->toString() ?: null,
            situation:            $request->string('situation')->toString() ?: null,
            creditLimit:          $request->filled('credit_limit') ? (float) $request->input('credit_limit') : null,
            email:                $request->string('email')->toString() ?: null,
            birthDate:            $request->string('birth_date')->toString() ?: null,
            notes:                $request->string('notes')->toString() ?: null,
            addresses:            $request->array('addresses') ?: [],
            contacts:             $request->array('contacts') ?: [],
            tags:                 $request->array('tags') ?: [],
            civilStatus:          $request->string('civil_status')->toString() ?: null,
            spouseName:           $request->string('spouse_name')->toString() ?: null,
            spouseDocument:       $request->filled('spouse_document') ? preg_replace('/\D/', '', $request->string('spouse_document')->toString()) : null,
            spousePhone:          $request->string('spouse_phone')->toString() ?: null,
            spouseEmployer:       $request->string('spouse_employer')->toString() ?: null,
            spouseIncome:         $request->filled('spouse_income') ? (float) $request->input('spouse_income') : null,
            guarantorName:        $request->string('guarantor_name')->toString() ?: null,
            guarantorDocument:    $request->filled('guarantor_document') ? preg_replace('/\D/', '', $request->string('guarantor_document')->toString()) : null,
            guarantorPhone:       $request->string('guarantor_phone')->toString() ?: null,
            guarantorAddress:     $request->string('guarantor_address')->toString() ?: null,
            guarantorIncome:      $request->filled('guarantor_income') ? (float) $request->input('guarantor_income') : null,
            commercialReferences: $request->array('commercial_references') ?: [],
            spouseProfession:     $request->string('spouse_profession')->toString() ?: null,
            spouseBirthDate:      $request->string('spouse_birth_date')->toString() ?: null,
            spouseGender:         $request->string('spouse_gender')->toString() ?: null,
            guarantorProfession:  $request->string('guarantor_profession')->toString() ?: null,
            guarantorBirthDate:   $request->string('guarantor_birth_date')->toString() ?: null,
            guarantorGender:      $request->string('guarantor_gender')->toString() ?: null,
            purchaseReferences:   $request->array('purchase_references') ?: [],
        );
    }
}
