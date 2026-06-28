<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->foreignUuid('price_list_id')
                ->nullable()
                ->after('carrier_id')
                ->constrained('price_lists', 'uuid')
                ->nullOnDelete();

            $table->index(['tenant_id', 'price_list_id'], 'customers_tenant_price_list_idx');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex('customers_tenant_price_list_idx');
            $table->dropForeignIdFor(\App\Modules\Catalog\Models\PriceList::class, 'price_list_id');
            $table->dropColumn('price_list_id');
        });
    }
};
