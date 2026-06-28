<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_collections', function (Blueprint $table): void {
            if (! Schema::hasColumn('catalog_collections', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('is_active');
            }

            if (! Schema::hasColumn('catalog_collections', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_public');
            }
        });
    }

    public function down(): void
    {
        Schema::table('catalog_collections', function (Blueprint $table): void {
            if (Schema::hasColumn('catalog_collections', 'is_public')) {
                $table->dropColumn('is_public');
            }
            if (Schema::hasColumn('catalog_collections', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });
    }
};
