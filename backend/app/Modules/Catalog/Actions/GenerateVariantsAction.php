<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\GenerateVariantsDTO;
use App\Modules\Catalog\Enums\ProductTypeEnum;
use App\Modules\Catalog\Events\VariantCreated;
use App\Modules\Catalog\Models\Attribute;
use App\Modules\Catalog\Models\Grid;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Variant;
use App\Shared\Exceptions\BusinessException;
use App\Shared\Exceptions\NotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class GenerateVariantsAction
{
    /**
     * Gera variantes para um produto do tipo "variable" a partir de uma Grade.
     *
     * Suporta grades multi-dimensionais: quando a grade contém atributos de
     * múltiplos grupos (ex: Cor × Tamanho), o produto cartesiano é computado
     * e uma variante é criada para cada combinação (ex: Azul/P, Azul/M, Vermelho/P …).
     *
     * @return Variant[]
     */
    public function execute(GenerateVariantsDTO $dto): array
    {
        $product = Product::where('uuid', $dto->productId)->first();

        if ($product === null) {
            throw new NotFoundException("Produto '{$dto->productId}' não encontrado.");
        }

        if ($product->type !== ProductTypeEnum::Variable) {
            throw new BusinessException('Somente produtos do tipo "variable" suportam geração automática de variantes.');
        }

        // Carrega atributos com seus grupos para agrupar por dimensão
        $grid = Grid::where('uuid', $dto->gridId)
            ->with(['attributes' => fn ($q) => $q->with('group')])
            ->first();

        if ($grid === null) {
            throw new NotFoundException("Grade '{$dto->gridId}' não encontrada.");
        }

        /** @var Collection<int, Attribute> $gridAttributes */
        $gridAttributes = $grid->attributes;

        if ($gridAttributes->isEmpty()) {
            throw new BusinessException('A grade não possui atributos configurados.');
        }

        // Agrupa os atributos por grupo (cada grupo = uma dimensão)
        $dimensions = $gridAttributes
            ->groupBy('attribute_group_id')
            ->values()
            ->map(fn (Collection $attrs) => $attrs->values()->all())
            ->all();

        // Produto cartesiano de todas as dimensões
        $combinations = $this->cartesian($dimensions);

        return DB::transaction(function () use ($dto, $product, $combinations): array {
            $product->update(['grid_id' => $dto->gridId]);

            $existingSkus = Variant::where('product_id', $product->uuid)
                ->pluck('sku')
                ->all();

            $created = [];

            foreach ($combinations as $index => $combo) {
                /** @var Attribute[] $combo */

                // SKU: PREFIX-VAL1-VAL2 (ex: CAM-AZUL-M)
                $skuSuffix = implode('-', array_map(
                    fn (Attribute $a) => strtoupper($a->value),
                    $combo,
                ));
                $sku = "{$dto->skuPrefix}-{$skuSuffix}";

                if (in_array($sku, $existingSkus, true)) {
                    continue;
                }

                // Nome: Label1 / Label2 (ex: Azul / M)
                $name = implode(' / ', array_map(
                    fn (Attribute $a) => $a->label ?: $a->value,
                    $combo,
                ));

                $variant = Variant::create([
                    'product_id'  => $product->uuid,
                    'sku'         => $sku,
                    'name'        => $name,
                    'price_cents' => $dto->basePriceCents,
                    'is_active'   => true,
                    'is_default'  => $index === 0,
                    'sort_order'  => $index,
                ]);

                // Vincula todos os atributos da combinação
                $pivot = collect($combo)->mapWithKeys(
                    fn (Attribute $a, int $i) => [$a->uuid => ['sort_order' => $i]],
                );
                $variant->attributes()->sync($pivot->all());

                // Snapshot JSONB da combinação (para exibição histórica)
                $gridCombination = collect($combo)->map(fn (Attribute $a) => [
                    'group_id'   => $a->attribute_group_id,
                    'group_name' => $a->group?->name,
                    'attr_id'    => $a->uuid,
                    'attr_value' => $a->label ?: $a->value,
                ])->values()->all();

                $variant->update(['grid_combination' => $gridCombination]);

                VariantCreated::dispatch($variant->refresh());

                $created[] = $variant->load('attributes');
            }

            return $created;
        });
    }

    /**
     * Produto cartesiano de N arrays.
     *
     * Exemplo: cartesian([[Red, Blue], [S, M, L]])
     * → [[Red, S], [Red, M], [Red, L], [Blue, S], [Blue, M], [Blue, L]]
     *
     * @param  array<int, Attribute[]> $groups
     * @return array<int, Attribute[]>
     */
    private function cartesian(array $groups): array
    {
        $result = [[]];

        foreach ($groups as $group) {
            $tmp = [];
            foreach ($result as $existing) {
                foreach ($group as $item) {
                    $tmp[] = array_merge($existing, [$item]);
                }
            }
            $result = $tmp;
        }

        return $result;
    }
}
