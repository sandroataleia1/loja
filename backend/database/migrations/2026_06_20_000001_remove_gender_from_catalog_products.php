<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Step 02 — Remove campo gender de catalog_products
 *
 * O campo gender pertencia ao domínio de moda (male/female/unisex/child/all).
 * Substituído por unit_of_measure (adicionado na migration 000002).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            if (Schema::hasColumn('catalog_products', 'gender')) {
                $table->dropIndex(['tenant_id', 'gender']);
                $table->dropColumn('gender');
            }
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->string('gender', 20)->default('all')->after('type');
            $table->index(['tenant_id', 'gender']);
        });
    }
};
