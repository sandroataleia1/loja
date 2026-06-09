<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Canal de venda — entidade de primeira classe, não um enum.
 *
 * Cada canal possui suas próprias regras, preços, estoque, credenciais e sync.
 * Tipos suportados: PDV, INSTAGRAM, WHATSAPP, ECOMMERCE, MARKETPLACE.
 *
 * store_id nullable: presente em canais PDV (vincula ao ponto físico).
 * metadata: títulos SEO, copy social, hashtags, AI metadata futura.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->uuid('store_id')->nullable();      // PDV channels link to a physical store

            $table->string('name', 150);
            $table->string('type', 50);               // ChannelTypeEnum value
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();     // SEO titles, social copy, AI metadata

            $table->timestamps();
            $table->softDeletes();

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index(['tenant_id', 'type', 'is_active'], 'channel_tenant_type_idx');
            $table->index('store_id', 'channel_store_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
