<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona deleted_at a todas as tabelas cujos models estendem BaseModel
 * (que usa SoftDeletes) mas cuja migration original omitiu a coluna.
 *
 * Tabelas afetadas:
 *  - tenant_features
 *  - catalog_attribute_groups
 *  - media_assets
 *  - channel_credentials
 *  - channel_prices
 *  - channel_products
 *  - sale_discounts
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'tenant_features',
            'catalog_attribute_groups',
            'media_assets',
            'channel_credentials',
            'channel_prices',
            'channel_products',
            'sale_discounts',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->softDeletes()->after('updated_at');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'sale_discounts',
            'channel_products',
            'channel_prices',
            'channel_credentials',
            'media_assets',
            'catalog_attribute_groups',
            'tenant_features',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropSoftDeletes();
            });
        }
    }
};
