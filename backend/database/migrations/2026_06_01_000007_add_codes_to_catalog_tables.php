<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_brands', function (Blueprint $table): void {
            $table->string('code', 20)->nullable()->after('uuid');
            $table->unique(['tenant_id', 'code'], 'catalog_brands_tenant_code_unique');
        });

        Schema::table('catalog_categories', function (Blueprint $table): void {
            $table->string('code', 20)->nullable()->after('uuid');
            $table->unique(['tenant_id', 'code'], 'catalog_categories_tenant_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_brands', function (Blueprint $table): void {
            $table->dropUnique('catalog_brands_tenant_code_unique');
            $table->dropColumn('code');
        });

        Schema::table('catalog_categories', function (Blueprint $table): void {
            $table->dropUnique('catalog_categories_tenant_code_unique');
            $table->dropColumn('code');
        });
    }
};
