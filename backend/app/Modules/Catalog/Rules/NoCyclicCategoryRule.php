<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Rules;

use App\Modules\Catalog\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Garante que definir parent_id não cria um ciclo na hierarquia de categorias.
 *
 * Exemplos de ciclo proibido:
 *   A → B → C → A  (C tenta ser pai de A, que já é ancestral de C)
 *   A → A           (categoria como pai de si mesma)
 *
 * Usado em StoreCategoryRequest para criar E em atualizações (quando category_uuid
 * é informado no contexto do request, para ignorar a própria categoria).
 */
final readonly class NoCyclicCategoryRule implements ValidationRule
{
    /**
     * @param string|null $categoryUuid UUID da categoria sendo editada (null = criação nova)
     */
    public function __construct(private ?string $categoryUuid = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        // Não pode ser pai de si mesma
        if ($this->categoryUuid !== null && $value === $this->categoryUuid) {
            $fail('Uma categoria não pode ser pai de si mesma.');
            return;
        }

        // Percorre a cadeia de ancestrais do candidato a pai
        // Se encontrar a categoria atual, é ciclo
        $current = Category::where('uuid', $value)->first();

        while ($current !== null) {
            if ($this->categoryUuid !== null && $current->uuid === $this->categoryUuid) {
                $fail('Esta relação de pai/filho criaria um ciclo na hierarquia de categorias.');
                return;
            }

            $current = $current->parent_id
                ? Category::where('uuid', $current->parent_id)->first()
                : null;
        }
    }
}
