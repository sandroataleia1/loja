<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->string('qr_code_url', 500)->nullable()->after('metadata');
        });

        Schema::table('catalog_variants', function (Blueprint $table): void {
            $table->string('qr_code_url', 500)->nullable()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropColumn('qr_code_url');
        });
        Schema::table('catalog_variants', function (Blueprint $table): void {
            $table->dropColumn('qr_code_url');
        });
    }
};
