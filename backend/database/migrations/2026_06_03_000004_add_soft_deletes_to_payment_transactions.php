<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona deleted_at à payment_transactions.
 *
 * PaymentTransaction estende BaseModel que inclui SoftDeletes,
 * mas a migration original não criou a coluna deleted_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
