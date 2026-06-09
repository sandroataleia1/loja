<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_balances', function (Blueprint $table): void {
            $table->softDeletes()->after('reserved_quantity');
        });

        Schema::table('stock_reservations', function (Blueprint $table): void {
            $table->softDeletes()->after('released_at');
        });
    }

    public function down(): void
    {
        Schema::table('stock_reservations', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('inventory_balances', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
