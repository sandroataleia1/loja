<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela pivot que vincula grupos de atributos a categorias.
 *
 * Permite filtrar atributos relevantes por categoria ao cadastrar produtos.
 * Ex.: categoria "Hidráulica" → atributos "Diâmetro", "Material", "Comprimento".
 *
 * is_required: indica se o atributo é obrigatório para produtos dessa categoria.
 * sort_order:  ordem de exibição dos atributos no formulário de cadastro.
 *
 * A herança via ancestrais é resolvida em application layer pelo método
 * Category::relevantAttributeGroups(), que percorre a árvore de cima para baixo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_attribute_groups', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('catalog_categories', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('attribute_group_id')->constrained('catalog_attribute_groups', 'uuid')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'category_id', 'attribute_group_id'],
                'cat_attr_groups_unique',
            );
            $table->index(['tenant_id', 'category_id', 'sort_order'], 'cat_attr_groups_tenant_cat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_attribute_groups');
    }
};
