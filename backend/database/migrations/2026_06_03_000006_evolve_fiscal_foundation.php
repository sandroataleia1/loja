<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 11 — Fiscal Foundation.
 *
 * 1. tenant_fiscal_settings: adiciona environment, series, next_number
 * 2. fiscal_documents: adiciona series, number; inclui status 'draft'
 * 3. Cria fiscal_document_items — snapshot de item com dados tributários
 * 4. Cria fiscal_events — trilha de auditoria de eventos por documento
 * 5. Cria fiscal_responses — resposta raw do provedor por tentativa
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. tenant_fiscal_settings: environment + série ────────────────────
        Schema::table('tenant_fiscal_settings', function (Blueprint $table): void {
            // HOMOLOGATION | PRODUCTION — default homologation até go-live
            $table->string('environment', 20)->default('homologation')->after('is_active');
            // Série da nota (ex: 1, 2, 890 para contingência)
            $table->unsignedSmallInteger('series')->default(1)->after('environment');
            // Próximo número a emitir — mantido por série
            $table->unsignedInteger('next_number')->default(1)->after('series');
        });

        // ── 2. fiscal_documents: série e número ──────────────────────────────
        Schema::table('fiscal_documents', function (Blueprint $table): void {
            $table->unsignedSmallInteger('series')->nullable()->after('type');
            $table->unsignedInteger('number')->nullable()->after('series');

            // Índice para garantir unicidade de série+número por tenant
            $table->index(['tenant_id', 'type', 'series', 'number'], 'fiscal_docs_series_number_idx');
        });

        // ── 3. fiscal_document_items ─────────────────────────────────────────
        Schema::create('fiscal_document_items', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('fiscal_document_id')
                ->constrained('fiscal_documents', 'uuid')
                ->cascadeOnDelete();
            // nullable: item pode ser linha de serviço sem variant
            $table->foreignUuid('product_variant_id')
                ->nullable()
                ->constrained('catalog_variants', 'uuid')
                ->nullOnDelete();

            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('total_cents');

            // Snapshot imutável dos dados tributários no momento da emissão
            $table->jsonb('tax_snapshot')->default('{}');
            // {ncm, cfop, cst, pis, cofins, icms_cst, aliquota_icms, base_icms, ...}

            $table->timestamps();

            $table->index('fiscal_document_id', 'fiscal_items_document_idx');
        });

        // ── 4. fiscal_events — trilha de auditoria ───────────────────────────
        // Append-only: cada ação (emissão, autorização, rejeição…) gera um evento
        Schema::create('fiscal_events', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('fiscal_document_id')
                ->constrained('fiscal_documents', 'uuid')
                ->cascadeOnDelete();

            $table->string('event_type', 30); // FiscalEventTypeEnum
            $table->jsonb('payload')->default('{}');   // dados enviados ao provedor
            $table->jsonb('response')->nullable();      // resposta recebida (pode ser nula em erros de rede)
            $table->string('provider', 30)->nullable(); // FiscalProviderEnum value

            // Imutável: sem updated_at
            $table->timestamp('created_at')->useCurrent();

            $table->index(['fiscal_document_id', 'created_at'], 'fiscal_events_document_idx');
            $table->index('event_type', 'fiscal_events_type_idx');
        });

        // ── 5. fiscal_responses — resposta raw do provedor por tentativa ─────
        Schema::create('fiscal_responses', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('fiscal_document_id')
                ->constrained('fiscal_documents', 'uuid')
                ->cascadeOnDelete();

            $table->string('provider', 30);         // FiscalProviderEnum
            $table->unsignedSmallInteger('status_code')->nullable(); // HTTP status ou código SEFAZ
            $table->string('message', 500)->nullable();
            $table->jsonb('raw_response')->nullable(); // payload completo sem transformações

            $table->timestamp('created_at')->useCurrent();

            $table->index(['fiscal_document_id', 'created_at'], 'fiscal_responses_document_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_responses');
        Schema::dropIfExists('fiscal_events');
        Schema::dropIfExists('fiscal_document_items');

        Schema::table('fiscal_documents', function (Blueprint $table): void {
            $table->dropIndex('fiscal_docs_series_number_idx');
            $table->dropColumn(['series', 'number']);
        });

        Schema::table('tenant_fiscal_settings', function (Blueprint $table): void {
            $table->dropColumn(['environment', 'series', 'next_number']);
        });
    }
};
