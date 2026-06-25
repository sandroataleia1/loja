<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Tabelas de preço do tenant ────────────────────────────────────────
        Schema::create('price_lists', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('code', 20);
            // retail | wholesale | representative | special | cost
            $table->string('type', 20)->default('retail');
            $table->char('currency', 3)->default('BRL');
            $table->decimal('max_discount_percent', 5, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active', 'is_default']);
            $table->index(['tenant_id', 'valid_from', 'valid_to']);
        });

        // ── Preços por produto/variante × tabela ──────────────────────────────
        Schema::create('product_prices', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('price_list_id')
                ->constrained('price_lists', 'uuid')
                ->cascadeOnDelete();
            $table->uuid('product_id')->nullable();
            $table->uuid('variant_id')->nullable();
            $table->foreign('product_id')->references('uuid')->on('catalog_products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('uuid')->on('catalog_variants')->cascadeOnDelete();
            $table->integer('price_cents');
            $table->integer('min_price_cents')->nullable();
            $table->integer('packaging_price_cents')->nullable();
            $table->decimal('packaging_qty', 10, 4)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            // Unicidade: 1 preço por (tabela × produto × variante)
            $table->unique(['price_list_id', 'product_id', 'variant_id'], 'uq_product_price_combo');
            $table->index(['tenant_id', 'price_list_id', 'variant_id']);
            $table->index(['tenant_id', 'price_list_id', 'product_id']);
        });

        // CHECK: pelo menos product_id ou variant_id deve estar preenchido
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE product_prices ADD CONSTRAINT chk_product_or_variant
             CHECK (product_id IS NOT NULL OR variant_id IS NOT NULL)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('price_lists');
    }
};
