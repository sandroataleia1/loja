<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 1 — Authentication & Tenant Bootstrap.
 *
 * tenants: adiciona code, trade_name, legal_name, document, email, phone.
 * stores: adiciona type (headquarters, branch, etc.).
 * channels: adiciona code (CAN0001, CAN0002, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Tenants ───────────────────────────────────────────────────────────
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('code', 20)->nullable()->unique()->after('uuid');
            $table->string('trade_name', 150)->nullable()->after('code');
            $table->string('legal_name', 200)->nullable()->after('trade_name');
            $table->string('document', 20)->nullable()->after('legal_name');
            $table->string('email', 150)->nullable()->after('document');
            $table->string('phone', 30)->nullable()->after('email');
        });

        // ── Stores ────────────────────────────────────────────────────────────
        Schema::table('stores', function (Blueprint $table): void {
            $table->string('type', 50)->default('headquarters')->after('code');
        });

        // ── Channels ──────────────────────────────────────────────────────────
        Schema::table('channels', function (Blueprint $table): void {
            $table->string('code', 20)->nullable()->after('uuid');
            $table->index(['tenant_id', 'code'], 'channel_tenant_code_idx');
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table): void {
            $table->dropIndex('channel_tenant_code_idx');
            $table->dropColumn('code');
        });

        Schema::table('stores', function (Blueprint $table): void {
            $table->dropColumn('type');
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['code', 'trade_name', 'legal_name', 'document', 'email', 'phone']);
        });
    }
};
