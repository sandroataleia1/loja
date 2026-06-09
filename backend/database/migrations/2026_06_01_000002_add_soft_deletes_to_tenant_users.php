<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_users', function (Blueprint $table): void {
            $table->softDeletes()->after('joined_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_users', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
