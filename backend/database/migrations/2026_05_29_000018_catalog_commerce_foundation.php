<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fundação comercial do catálogo.
 *
 * Esta migration adiciona a camada de conteúdo comercial, analytics,
 * mídia desacoplada e histórico de preços — preparando o catálogo para:
 * - Social commerce
 * - Catálogo publicável
 * - Motor de IA futuro
 * - Omnichannel
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
        |----------------------------------------------------------------------
        | media_assets — domínio desacoplado de mídia
        |
        | Reutilizável: um asset pode ser usado em produtos, campanhas,
        | banners, e-mail marketing, etc.
        | metadata JSONB suporta: thumbnails, dimensões, cores dominantes,
        | AI labels futuros, embeddings visuais.
        |----------------------------------------------------------------------
        */
        Schema::create('media_assets', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->string('type', 20);                         // image|video|banner|document
            $table->string('path', 1000);                       // caminho no storage
            $table->string('original_name', 255)->nullable();   // nome original do upload
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // bytes
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users', 'uuid')->nullOnDelete();
            // AI future: thumbnails, dimensions, dominant_colors, visual_embeddings, auto_labels
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'uploaded_by']);
        });

        /*
        |----------------------------------------------------------------------
        | product_media — pivot desacoplado produto ↔ mídia
        |
        | Desacoplado de catalog_images (URL-based, legacy).
        | Suporta ordenação e designação de imagem primária.
        |----------------------------------------------------------------------
        */
        Schema::create('product_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('product_id')->constrained('catalog_products', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('media_asset_id')->constrained('media_assets', 'uuid')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_primary')->default(false);

            $table->unique(['product_id', 'media_asset_id']);
            $table->index(['product_id', 'position']);
            $table->index(['product_id', 'is_primary']);
        });

        /*
        |----------------------------------------------------------------------
        | catalog_collection_products — pivot muitos-para-muitos comercial
        |
        | Separa coleções editoriais (product.collection_id = BelongsTo)
        | de coleções comerciais (many-to-many = campanhas, sazonais).
        |
        | Permite: "Black Friday" E "Moda Casual" ao mesmo tempo.
        |----------------------------------------------------------------------
        */
        Schema::create('catalog_collection_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('product_id')->constrained('catalog_products', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('collection_id')->constrained('catalog_collections', 'uuid')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->unique(['product_id', 'collection_id']);
            $table->index(['collection_id', 'sort_order']);
        });

        /*
        |----------------------------------------------------------------------
        | catalog_price_history — histórico de preços
        |
        | IA promocional futura dependerá de:
        | - Histórico de preço × tempo
        | - Comportamento de venda pós-alteração
        | - Sazonalidade de preço
        |----------------------------------------------------------------------
        */
        Schema::create('catalog_price_history', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('catalog_products', 'uuid')->cascadeOnDelete();
            $table->uuid('variant_id')->nullable(); // preço por variante
            $table->integer('old_price_cents');
            $table->integer('new_price_cents');
            $table->foreignUuid('changed_by')->nullable()->constrained('users', 'uuid')->nullOnDelete();
            $table->timestamp('changed_at');
            $table->string('reason', 255)->nullable();

            $table->index(['tenant_id', 'product_id', 'changed_at']);
            $table->index(['tenant_id', 'variant_id', 'changed_at']);
        });

        /*
        |----------------------------------------------------------------------
        | catalog_products — campos de conteúdo comercial e analytics
        |----------------------------------------------------------------------
        */
        Schema::table('catalog_products', function (Blueprint $table): void {
            // ── Conteúdo comercial ─────────────────────────────────────────
            $table->text('marketing_description')->nullable()->after('short_description');
            $table->text('internal_notes')->nullable()->after('marketing_description');
            $table->boolean('is_publishable')->default(false)->after('is_digital');
            $table->string('season', 50)->nullable()->after('gender');
            $table->date('launch_date')->nullable()->after('season');

            // ── Analytics foundation — AI futura e campanhas ───────────────
            // Velocidade de vendas: unidades/dia (média móvel)
            $table->decimal('sales_velocity', 8, 4)->nullable()->after('metadata');
            // Dias desde a última venda (0 = vendido hoje, null = nunca vendido)
            $table->unsignedSmallInteger('days_without_sale')->nullable()->after('sales_velocity');
            // Idade do estoque em dias (desde última reposição)
            $table->unsignedSmallInteger('stock_age')->nullable()->after('days_without_sale');
            // Timestamp da última venda — preenche automaticamente por listener
            $table->timestamp('last_sale_at')->nullable()->after('stock_age');

            $table->index(['tenant_id', 'is_publishable']);
            $table->index(['tenant_id', 'season']);
            $table->index(['tenant_id', 'launch_date']);
            $table->index(['tenant_id', 'last_sale_at']);
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'last_sale_at']);
            $table->dropIndex(['tenant_id', 'launch_date']);
            $table->dropIndex(['tenant_id', 'season']);
            $table->dropIndex(['tenant_id', 'is_publishable']);
            $table->dropColumn([
                'marketing_description', 'internal_notes', 'is_publishable',
                'season', 'launch_date', 'sales_velocity', 'days_without_sale',
                'stock_age', 'last_sale_at',
            ]);
        });

        Schema::dropIfExists('catalog_price_history');
        Schema::dropIfExists('catalog_collection_products');
        Schema::dropIfExists('product_media');
        Schema::dropIfExists('media_assets');
    }
};
