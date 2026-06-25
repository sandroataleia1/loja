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
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            personType:  PersonTypeEnum::from($request->string('person_type')->toString()),
            name:        $request->string('name')->toString(),
            tradeName:   $request->string('trade_name')->toString() ?: null,
            document:    $request->filled('document') ? preg_replace('/\D/', '', $request->string('document')->toString()) : null,
            rg:          $request->string('rg')->toString() ?: null,
            ie:          $request->string('ie')->toString() ?: null,
            im:          $request->string('im')->toString() ?: null,
            situation:   $request->string('situation')->toString() ?: null,
            creditLimit: $request->filled('credit_limit') ? (float) $request->input('credit_limit') : null,
            email:       $request->string('email')->toString() ?: null,
            birthDate:   $request->string('birth_date')->toString() ?: null,
            notes:       $request->string('notes')->toString() ?: null,
            addresses:   $request->array('addresses') ?: [],
            contacts:    $request->array('contacts') ?: [],
            tags:        $request->array('tags') ?: [],
        );
    }
}
