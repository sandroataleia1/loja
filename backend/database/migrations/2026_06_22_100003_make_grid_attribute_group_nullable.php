<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Torna catalog_grids.attribute_group_id nullable para suportar grades
 * multi-dimensionais (ex: Cor × Tamanho × Material).
 *
 * Antes: grade forçava um único AttributeGroup → impossível ter variantes Cor × Tamanho.
 * Depois: attribute_group_id é o grupo "primário" (opcional). Os atributos reais
 * pertencem a catalog_grid_items e podem vir de múltiplos grupos.
 *
 * Dados existentes não são alterados (grids existentes mantêm seu attribute_group_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_grids', function (Blueprint $table): void {
            // Remove a constraint NOT NULL (mantém a FK para integridade referencial)
            $table->uuid('attribute_group_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_grids', function (Blueprint $table): void {
            $table->uuid('attribute_group_id')->nullable(false)->change();
        });
    }
};
