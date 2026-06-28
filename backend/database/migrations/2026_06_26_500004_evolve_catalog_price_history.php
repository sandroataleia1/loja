<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_price_history', function (Blueprint $table): void {
            // old_price_cents pode ser null no primeiro registro (cadastro inicial)
            $table->integer('old_price_cents')->nullable()->change();

            // Rastreia em qual lista de preços a alteração ocorreu
            $table->foreignUuid('price_list_id')
                ->nullable()
                ->after('variant_id')
                ->constrained('price_lists', 'uuid')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_price_history', function (Blueprint $table): void {
            $table->dropForeignIdFor(\App\Modules\Catalog\Models\PriceList::class, 'price_list_id');
            $table->dropColumn('price_list_id');
            $table->integer('old_price_cents')->nullable(false)->change();
        });
    }
};
