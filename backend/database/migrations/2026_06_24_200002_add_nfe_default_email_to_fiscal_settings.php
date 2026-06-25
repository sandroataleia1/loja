<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_fiscal_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenant_fiscal_settings', 'nfe_default_email')) {
                $table->string('nfe_default_email', 150)->nullable()->after('fiscal_retry_max');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenant_fiscal_settings', function (Blueprint $table): void {
            $table->dropColumn('nfe_default_email');
        });
    }
};
