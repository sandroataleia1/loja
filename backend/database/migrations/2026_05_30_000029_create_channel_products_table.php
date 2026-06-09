<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Publicação de produto por canal — opt-in explícito.
 *
 * Produto NÃO existe em um canal automaticamente.
 * Publicação é assíncrona (via Job) e rastreável via sync_status.
 *
 * external_reference: SKU/ID no sistema externo (Instagram product ID, ML SKU, etc.)
 * sync_status: PENDING → SYNCED | FAILED | OUTDATED
 * metadata: copy por canal (título SEO, descrição marketplace, hashtags).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_products', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id');                 // denormalized, no FK (cross-module)
            $table->foreignUuid('channel_id')->constrained('channels', 'uuid')->cascadeOnDelete();
            $table->uuid('product_id');                // references catalog_products.uuid (no FK cross-module)

            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('external_reference', 255)->nullable();  // platform-specific ID
            $table->string('sync_status', 30)->default('pending');  // ChannelSyncStatusEnum
            $table->jsonb('metadata')->nullable();      // per-channel copy overrides

            $table->timestamps();

            // ── Constraints ──────────────────────────────────────────────────
            $table->unique(['channel_id', 'product_id'], 'channel_product_unique');

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index(['tenant_id', 'channel_id', 'sync_status'], 'channel_product_sync_idx');
            $table->index(['tenant_id', 'product_id'],                'channel_product_catalog_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_products');
    }
};
