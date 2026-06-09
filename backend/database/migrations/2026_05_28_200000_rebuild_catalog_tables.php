<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('products');

        // ── Brands ────────────────────────────────────────────────────────────
        Schema::create('catalog_brands', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->text('description')->nullable();
            $table->string('logo_url', 500)->nullable();
            $table->string('website_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'is_active']);
        });

        // ── Categories ────────────────────────────────────────────────────────
        Schema::create('catalog_categories', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->text('description')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'parent_id']);
            $table->index(['tenant_id', 'is_active', 'sort_order']);
        });

        // Self-referencing FK added after primary key is committed
        Schema::table('catalog_categories', function (Blueprint $table): void {
            $table->foreign('parent_id')->references('uuid')->on('catalog_categories')->nullOnDelete();
        });

        // ── Collections ───────────────────────────────────────────────────────
        Schema::create('catalog_collections', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->text('description')->nullable();
            $table->string('cover_url', 500)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'starts_at', 'ends_at']);
        });

        // ── Attribute Groups ──────────────────────────────────────────────────
        // Ex: "Tamanho", "Cor", "Material"
        Schema::create('catalog_attribute_groups', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->string('type', 20)->default('text'); // text | color | number | boolean
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'sort_order']);
        });

        // ── Attributes ────────────────────────────────────────────────────────
        // Ex: P, M, G, GG | Vermelho, Azul, Preto
        Schema::create('catalog_attributes', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('attribute_group_id')->constrained('catalog_attribute_groups', 'uuid')->cascadeOnDelete();
            $table->string('value', 100);  // stored value: "P", "#FF0000"
            $table->string('label', 100);  // display label: "Pequeno", "Vermelho"
            $table->string('color_hex', 7)->nullable();
            $table->integer('sort_order')->default(0);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'attribute_group_id', 'value']);
            $table->index(['tenant_id', 'attribute_group_id', 'sort_order']);
        });

        // ── Grids ─────────────────────────────────────────────────────────────
        // Grade de tamanhos: ex "P/M/G/GG", "34-36-38-40", "PP/P/M/G/GG/XG"
        Schema::create('catalog_grids', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('attribute_group_id')->constrained('catalog_attribute_groups', 'uuid')->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'attribute_group_id']);
        });

        // ── Grid Items ────────────────────────────────────────────────────────
        Schema::create('catalog_grid_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('grid_id')->constrained('catalog_grids', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('attribute_id')->constrained('catalog_attributes', 'uuid')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);

            $table->unique(['grid_id', 'attribute_id']);
            $table->index(['grid_id', 'sort_order']);
        });

        // ── Products ──────────────────────────────────────────────────────────
        Schema::create('catalog_products', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('brand_id')->nullable()->constrained('catalog_brands', 'uuid')->nullOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('catalog_categories', 'uuid')->nullOnDelete();
            $table->foreignUuid('collection_id')->nullable()->constrained('catalog_collections', 'uuid')->nullOnDelete();
            $table->foreignUuid('grid_id')->nullable()->constrained('catalog_grids', 'uuid')->nullOnDelete();

            $table->string('name', 200);
            $table->string('slug', 220);
            $table->text('description')->nullable();
            $table->string('short_description', 500)->nullable();

            // simple: produto único; variable: tem variantes; kit: combo
            $table->string('type', 20)->default('simple');
            // Público-alvo de moda
            $table->string('gender', 20)->default('all'); // male|female|unisex|child|all
            // Fluxo de publicação
            $table->string('status', 20)->default('draft'); // draft|active|inactive|archived

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_digital')->default(false);

            // SEO & discovery
            $table->jsonb('seo')->nullable();
            // AI future: embeddings, auto-tags, recommendations
            $table->jsonb('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'category_id']);
            $table->index(['tenant_id', 'brand_id']);
            $table->index(['tenant_id', 'collection_id']);
            $table->index(['tenant_id', 'gender']);
            $table->index(['tenant_id', 'is_featured']);
            $table->index(['tenant_id', 'created_at']);
        });

        // ── Variants ──────────────────────────────────────────────────────────
        // Cada combinação de atributos (cor + tamanho) = 1 variant = 1 SKU
        Schema::create('catalog_variants', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('catalog_products', 'uuid')->cascadeOnDelete();

            $table->string('sku', 100);
            $table->string('name', 200)->nullable(); // "Azul / M" — auto-gerado
            $table->string('barcode', 50)->nullable();
            $table->string('gtin', 14)->nullable(); // EAN-13 / UPC-A

            $table->integer('price_cents')->default(0);
            $table->integer('cost_cents')->nullable();
            $table->integer('compare_at_cents')->nullable(); // preço "de" para promoções

            $table->integer('weight_g')->nullable();
            $table->jsonb('dimensions')->nullable(); // {width_cm, height_cm, depth_cm}

            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // variant exibida por padrão
            $table->integer('sort_order')->default(0);

            // Estoque futuro — Inventory module consumirá essa FK
            // $table->integer('stock_quantity') aqui propositalmente OMITIDO
            // pois pertence ao módulo Inventory

            $table->jsonb('metadata')->nullable(); // AI: similaridade, recomendações

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'barcode']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'product_id', 'sort_order']);
        });

        // ── Variant Attributes ────────────────────────────────────────────────
        Schema::create('catalog_variant_attributes', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('variant_id')->constrained('catalog_variants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('attribute_id')->constrained('catalog_attributes', 'uuid')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);

            $table->unique(['variant_id', 'attribute_id']);
            $table->index(['variant_id', 'sort_order']);
        });

        // ── Images ────────────────────────────────────────────────────────────
        // Polimórfico: catalog_products | catalog_variants | catalog_categories
        Schema::create('catalog_images', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->uuidMorphs('imageable');  // imageable_id + imageable_type

            $table->string('url', 1000);
            $table->string('thumbnail_url', 1000)->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);

            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();

            // AI future: auto-labels, dominant colors, visual embeddings
            $table->jsonb('metadata')->nullable();

            $table->timestamps();

            $table->index(['imageable_type', 'imageable_id', 'sort_order']);
            $table->index(['tenant_id', 'imageable_type', 'imageable_id']);
        });

        // ── Tags ──────────────────────────────────────────────────────────────
        Schema::create('catalog_tags', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->string('type', 50)->nullable(); // product | collection | etc.
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'type']);
        });

        // ── Taggables (polymorphic pivot) ─────────────────────────────────────
        Schema::create('catalog_taggables', function (Blueprint $table): void {
            $table->foreignUuid('tag_id')->constrained('catalog_tags', 'uuid')->cascadeOnDelete();
            $table->uuidMorphs('taggable'); // taggable_id + taggable_type

            $table->primary(['tag_id', 'taggable_id', 'taggable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_taggables');
        Schema::dropIfExists('catalog_tags');
        Schema::dropIfExists('catalog_images');
        Schema::dropIfExists('catalog_variant_attributes');
        Schema::dropIfExists('catalog_variants');
        Schema::dropIfExists('catalog_products');
        Schema::dropIfExists('catalog_grid_items');
        Schema::dropIfExists('catalog_grids');
        Schema::dropIfExists('catalog_attributes');
        Schema::dropIfExists('catalog_attribute_groups');
        Schema::dropIfExists('catalog_collections');
        Schema::dropIfExists('catalog_categories');
        Schema::dropIfExists('catalog_brands');
    }
};
