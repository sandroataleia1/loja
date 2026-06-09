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
        public readonly ?string        $email,
        public readonly ?string        $birthDate,
        public readonly ?string        $notes,
        public readonly array          $addresses,
        public readonly array          $contacts,
        public readonly array          $tags,
        // backward-compat legacy fields (kept nullable, not used in new flow)
        public readonly ?string        $cpf  = null,
        public readonly ?string        $cnpj = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            personType: PersonTypeEnum::from($request->string('person_type')->toString()),
            name:       $request->string('name')->toString(),
            tradeName:  $request->string('trade_name')->toString() ?: null,
            document:   $request->string('document')->toString() ?: null,
            email:      $request->string('email')->toString() ?: null,
            birthDate:  $request->string('birth_date')->toString() ?: null,
            notes:      $request->string('notes')->toString() ?: null,
            addresses:  $request->array('addresses') ?: [],
            contacts:   $request->array('contacts') ?: [],
            tags:       $request->array('tags') ?: [],
        );
    }
}
