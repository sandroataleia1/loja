<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Muda aggregate_uuid de uuid → varchar(100) na tabela analytics_events.
 *
 * O AnalyticsEventDTO aceita string livre para aggregateUuid, não necessariamente
 * um UUID válido — pode ser qualquer identificador de aggregate (UUID de produto,
 * de pedido omnichannel externo, ou ID de campanha em formato livre).
 * O tipo uuid do PostgreSQL rejeita strings não-UUID, causando erros silenciosos
 * no AnalyticsEventRecorder (que engole exceções).
 *
 * Solução: varchar(100) — event store analítico não precisa de FK ou type constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Dropa o índice que inclui aggregate_uuid antes de alterar o tipo
        DB::statement('DROP INDEX IF EXISTS analytics_event_aggregate_idx');

        // Altera o tipo de uuid para varchar via USING cast
        DB::statement('
            ALTER TABLE analytics_events
            ALTER COLUMN aggregate_uuid TYPE VARCHAR(100)
            USING aggregate_uuid::text
        ');

        // Recria o índice
        DB::statement('
            CREATE INDEX analytics_event_aggregate_idx
            ON analytics_events (tenant_id, aggregate_type, aggregate_uuid, occurred_at)
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS analytics_event_aggregate_idx');

        DB::statement('
            ALTER TABLE analytics_events
            ALTER COLUMN aggregate_uuid TYPE UUID
            USING aggregate_uuid::uuid
        ');

        DB::statement('
            CREATE INDEX analytics_event_aggregate_idx
            ON analytics_events (tenant_id, aggregate_type, aggregate_uuid, occurred_at)
        ');
    }
};
