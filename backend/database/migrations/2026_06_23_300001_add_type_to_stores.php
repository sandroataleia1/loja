<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('stores', 'type')) {
            return;
        }

        Schema::table('stores', function (Blueprint $table): void {
            $table->string('type', 20)
                  ->default('store')
                  ->after('code')
                  ->comment('store | warehouse | yard | ecommerce');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->dropColumn('type');
        });
    }
};
