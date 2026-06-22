<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação para criação de Grade.
 *
 * attribute_group_id é opcional: quando informado (grade simples de 1 dimensão),
 * é salvo como referência primária. Quando ausente, a grade é multi-dimensional
 * e os grupos são derivados dos atributos selecionados.
 *
 * attribute_ids pode conter atributos de MÚLTIPLOS grupos, habilitando
 * geração cartesiana de variantes (ex: Cor × Tamanho = N×M variantes).
 */
final class StoreGridRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'attribute_group_id' => ['nullable', 'uuid', 'exists:catalog_attribute_groups,uuid'],
            'name'               => ['required', 'string', 'max:100'],
            'description'        => ['nullable', 'string', 'max:500'],
            'attribute_ids'      => ['required', 'array', 'min:1'],
            'attribute_ids.*'    => ['uuid', 'exists:catalog_attributes,uuid'],
        ];
    }
}
