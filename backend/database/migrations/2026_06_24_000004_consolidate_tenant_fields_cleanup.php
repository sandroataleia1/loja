<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registra no histórico a duplicidade entre:
 *   - 2026_05_30_000042_sprint1_schema_additions (adicionou code/trade_name/etc)
 *   - 2026_06_22_100001_add_fiscal_fields_to_tenants (repetiu os mesmos campos)
 *
 * Ambas já rodaram em produção. Esta migration não altera dados — apenas
 * documenta a consolidação e garante que nenhum campo ficou faltando caso
 * um dos dois scripts não tenha sido executado.
 *
 * Usa hasColumn() em todos os campos para ser completamente idempotente.
 *
 * NÃO há down() destrutivo — campos de empresa nunca devem ser removidos.
 */
return new class extends Migration
{
    /** @var array<string, array{type: string, length?: int, after: string}> */
    private array $tenantFields = [
        'code'       => ['type' => 'string', 'length' => 50,  'after' => 'uuid',        'nullable' => true],
        'legal_name' => ['type' => 'string', 'length' => 200, 'after' => 'name',        'nullable' => true],
        'trade_name' => ['type' => 'string', 'length' => 200, 'after' => 'legal_name',  'nullable' => true],
        'document'   => ['type' => 'string', 'length' => 18,  'after' => 'trade_name',  'nullable' => true],
        'email'      => ['type' => 'string', 'length' => 200, 'after' => 'document',    'nullable' => true],
        'phone'      => ['type' => 'string', 'length' => 20,  'after' => 'email',       'nullable' => true],
    ];

    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            foreach ($this->tenantFields as $column => $def) {
                if (! Schema::hasColumn('tenants', $column)) {
                    // Campo ausente: adiciona (ambiente que só rodou uma das duas migrations)
                    $col = $table->string($column, $def['length'])->nullable()->after($def['after']);

                    if ($column === 'code') {
                        $col->unique();
                    }
                }
            }
        });
    }

    public function down(): void
    {
        // Não remove — campos de empresa são críticos.
        // Para reverter, crie uma migration explícita com justificativa de negócio.
    }
};
