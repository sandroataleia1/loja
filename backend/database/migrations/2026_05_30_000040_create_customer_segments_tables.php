<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Segmentos de clientes — fundação para campanhas e automações.
 *
 * Segmento é um grupo nomeado de clientes.
 * Regras de elegibilidade serão implementadas em CustomerSegmentRule (futuro).
 * Por enquanto: membership explícita via CustomerSegmentMember.
 *
 * is_system: segmentos criados automaticamente pelo sistema
 *   (ex: VIP, Inativos, Novos) — não podem ser deletados pelo usuário.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_segments', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();

            $table->string('name', 100);
            $table->string('slug', 100);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug'], 'customer_segment_slug_unique');
        });

        Schema::create('customer_segment_members', function (Blueprint $table): void {
            $table->foreignUuid('customer_segment_id')->constrained('customer_segments', 'uuid')->cascadeOnDelete();
            $table->uuid('customer_id');
            $table->uuid('tenant_id');
            $table->timestamp('added_at')->useCurrent();

            $table->primary(['customer_segment_id', 'customer_id'], 'segment_member_pk');
            $table->index(['tenant_id', 'customer_id'], 'segment_member_customer_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_segment_members');
        Schema::dropIfExists('customer_segments');
    }
};
