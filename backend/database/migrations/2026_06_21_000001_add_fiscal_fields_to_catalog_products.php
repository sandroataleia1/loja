<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona dados fiscais ao produto (NCM, CEST, CFOP, Origem).
 *
 * Os campos já existem em catalog_variants para rastreio por variante;
 * aqui ficam no produto como dados padrão usados na emissão de NF-e.
 * Quando a variante tiver seus próprios campos, eles têm precedência.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            // NCM — Nomenclatura Comum do Mercosul (8 dígitos, p.ex. 6802.92.00)
            $table->string('ncm', 10)->nullable()->after('unit_of_measure');
            // CEST — Código Especificador da Substituição Tributária (7 dígitos)
            $table->string('cest', 9)->nullable()->after('ncm');
            // CFOP padrão de saída (4 dígitos, p.ex. 5102 = venda merc. adq./rec. de terceiros)
            $table->string('cfop_default', 5)->nullable()->after('cest');
            // Origem (0 = Nacional … 8, conforme tabela SEFAZ)
            $table->unsignedTinyInteger('origin_code')->default(0)->after('cfop_default');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropColumn(['ncm', 'cest', 'cfop_default', 'origin_code']);
        });
    }
};
