<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Shared\Models\BaseModel;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

final class Category extends BaseModel
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
    protected $table = 'catalog_categories';

    protected $fillable = [
        'tenant_id',
        'code',
        'parent_id',
        'name',
        'slug',
        'description',
        'image_url',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
            'metadata'   => 'array',
        ]);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id', 'uuid');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id', 'uuid');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'catalog_product_categories',
            'category_id',
            'product_id',
            'uuid',
            'uuid',
        )->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(ProductImage::class, 'imageable', 'imageable_type', 'imageable_id', 'uuid');
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    // ── Navegação na árvore (CTE recursivo PostgreSQL) ────────────────────────

    /**
     * Retorna todos os descendentes da categoria em todos os níveis.
     * Ordenados por profundidade (depth) e sort_order.
     * Usa CTE recursivo (PostgreSQL) — eficiente em qualquer profundidade.
     */
    public function allDescendants(): Collection
    {
        $rows = DB::select("
            WITH RECURSIVE descendants AS (
                SELECT uuid, parent_id, name, slug, sort_order, is_active,
                       deleted_at, tenant_id, 1 AS depth
                FROM catalog_categories
                WHERE parent_id = ?
                  AND deleted_at IS NULL

                UNION ALL

                SELECT c.uuid, c.parent_id, c.name, c.slug, c.sort_order,
                       c.is_active, c.deleted_at, c.tenant_id, d.depth + 1
                FROM catalog_categories c
                INNER JOIN descendants d ON c.parent_id = d.uuid
                WHERE c.deleted_at IS NULL
            )
            SELECT * FROM descendants
            ORDER BY depth, sort_order
        ", [$this->uuid]);

        return static::hydrate(
            array_map(fn (object $row) => (array) $row, $rows),
        );
    }

    /**
     * Retorna a cadeia de ancestrais da raiz até o pai imediato (sem incluir self).
     * Ordenados da raiz para o pai imediato (top-down).
     */
    public function ancestors(): Collection
    {
        if ($this->parent_id === null) {
            return new Collection();
        }

        $rows = DB::select("
            WITH RECURSIVE ancestors AS (
                SELECT uuid, parent_id, name, slug, sort_order, is_active,
                       deleted_at, tenant_id, 0 AS depth
                FROM catalog_categories
                WHERE uuid = ?
                  AND deleted_at IS NULL

                UNION ALL

                SELECT c.uuid, c.parent_id, c.name, c.slug, c.sort_order,
                       c.is_active, c.deleted_at, c.tenant_id, a.depth + 1
                FROM catalog_categories c
                INNER JOIN ancestors a ON c.uuid = a.parent_id
                WHERE c.deleted_at IS NULL
            )
            SELECT * FROM ancestors
            WHERE uuid != ?
            ORDER BY depth DESC
        ", [$this->parent_id, $this->uuid]);

        return static::hydrate(
            array_map(fn (object $row) => (array) $row, $rows),
        );
    }

    /**
     * Retorna a string de navegação tipo breadcrumb.
     * Ex.: "Elétrica > Cabos e Fios > Cabos Flexíveis"
     */
    public function breadcrumb(string $separator = ' > '): string
    {
        $chain = $this->ancestors()->push($this);

        return $chain->pluck('name')->implode($separator);
    }

    /**
     * Profundidade na árvore (raiz = 0, filho direto = 1, etc.).
     * Baseado na contagem de ancestrais.
     */
    public function depth(): int
    {
        return $this->ancestors()->count();
    }

    // ── Atributos vinculados ──────────────────────────────────────────────────

    public function attributeGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeGroup::class,
            'category_attribute_groups',
            'category_id',
            'attribute_group_id',
            'uuid',
            'uuid',
        )->withPivot(['sort_order', 'is_required'])->orderByPivot('sort_order');
    }

    /**
     * Retorna os grupos de atributos relevantes para esta categoria,
     * incluindo os herdados dos seus ancestrais (sem duplicatas).
     *
     * Lógica: percorre da raiz até a categoria atual e faz merge dos grupos,
     * mantendo o sort_order mais específico (descendente sobrescreve ancestral).
     */
    public function relevantAttributeGroups(): Collection
    {
        $chain = $this->ancestors()->push($this);

        $merged = collect();

        foreach ($chain as $category) {
            foreach ($category->attributeGroups as $group) {
                $merged->put($group->uuid, $group);
            }
        }

        return $merged->values();
    }
}
