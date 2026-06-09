<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            // Canal de origem da venda — omnichannel desde a fundação.
            // DEFAULT 'pdv' para compatibilidade com vendas existentes.
            $table->string('sales_channel', 20)->default('pdv')->after('fiscal_mode');

            $table->index(['tenant_id', 'sales_channel']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'sales_channel']);
            $table->dropColumn('sales_channel');
        });
    }
};
