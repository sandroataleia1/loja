<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Event Store analítico — append-only, separado do domain_event_logs (Audit).
 *
 * domain_event_logs  → compliance, replay operacional, 2 anos de retenção
 * analytics_events   → funil, atribuição, ML features, retenção indefinida
 *
 * aggregate_type + aggregate_uuid: permite reconstruir qualquer projeção
 * por entidade (customer journey, product funnel, etc.).
 *
 * metadata: atribuição padronizada — campaign_id, channel_id, source, medium.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id');                    // no FK — events are permanent

            $table->string('event_name', 150);            // ProductViewed, ProductSold, etc.
            $table->string('aggregate_type', 100);        // AggregateTypeEnum
            $table->uuid('aggregate_uuid');               // the entity this event is about

            $table->jsonb('payload');                     // event-specific data
            $table->jsonb('metadata')->nullable();        // attribution: campaign_id, channel_id, source, medium

            $table->string('correlation_id', 64)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            // ── Indexes ──────────────────────────────────────────────────────
            // Funnel: events for a specific entity (customer journey, product funnel)
            $table->index(['tenant_id', 'aggregate_type', 'aggregate_uuid', 'occurred_at'],
                'analytics_event_aggregate_idx');

            // Timeline: events by name across tenant (replay, pattern detection)
            $table->index(['tenant_id', 'event_name', 'occurred_at'],
                'analytics_event_name_idx');

            // Attribution: events linked to a campaign
            $table->index('correlation_id', 'analytics_event_correlation_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
