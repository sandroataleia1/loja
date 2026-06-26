<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('catalog_products', 'location')) {
            return;
        }

        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->string('location', 100)->nullable()->after('internal_notes');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropColumn('location');
        });
    }
};
