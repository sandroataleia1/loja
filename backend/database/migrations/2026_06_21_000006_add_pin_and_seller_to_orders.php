<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PIN de vendedor nos usuários
        Schema::table('users', function (Blueprint $table): void {
            $table->string('pin', 10)->nullable()->after('is_active');
        });

        // Vendedor vinculado via PIN no orçamento
        Schema::table('quotes', function (Blueprint $table): void {
            $table->foreignUuid('seller_id')->nullable()->after('customer_id')
                  ->constrained('users', 'uuid')->nullOnDelete();
        });

        // Vendedor vinculado via PIN no pedido
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignUuid('seller_id')->nullable()->after('customer_id')
                  ->constrained('users', 'uuid')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['seller_id']);
            $table->dropColumn('seller_id');
        });
        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropForeign(['seller_id']);
            $table->dropColumn('seller_id');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('pin');
        });
    }
};
