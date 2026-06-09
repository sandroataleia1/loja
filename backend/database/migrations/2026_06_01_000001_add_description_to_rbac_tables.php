<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona coluna description (nullable TEXT) às tabelas roles e permissions.
 * Permite documentar o propósito de cada role/permission diretamente no banco.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('slug');
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('module');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn('description');
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->dropColumn('description');
        });
    }
};
