<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Step 01 — Quantidades decimais e unidade de medida
 *
 * Mudanças:
 * 1. catalog_products — adiciona unit_of_measure (UN|M|M2|M3|KG|LT|CX|SC)
 * 2. sale_items — quantity e returned_quantity: INTEGER → DECIMAL(15,3)
 * 3. sale_return_items — quantity_returned: SMALLINT → DECIMAL(15,3)
 * 4. inventory_balances — quantity e reserved_quantity: INTEGER → DECIMAL(15,3)
 * 5. inventory_movements — todos os campos de qty: INTEGER → DECIMAL(15,3)
 * 6. inventory_adjustments — previous/new_quantity: INTEGER → DECIMAL(15,3)
 *    + recria coluna gerada difference como DECIMAL(15,3)
 * 7. stock_count_items — system/counted_quantity: INTEGER → DECIMAL(15,3)
 *    + recria coluna gerada difference como DECIMAL(15,3)
 * 8. purchase_order_items — quantity e received_quantity: INTEGER → DECIMAL(15,3)
 *    + recria coluna gerada total_cost com DECIMAL(15,3) como base
 * 9. purchase_receipt_items — quantity_received: INTEGER → DECIMAL(15,3)
 * 10. conditional_items — quantity, returned_quantity, sold_quantity: INTEGER → DECIMAL(15,3)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Produto: adicionar unit_of_measure ─────────────────────────────
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->string('unit_of_measure', 10)->nullable()->default('UN')->after('type');
        });

        // ── 2. sale_items ─────────────────────────────────────────────────────
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->decimal('quantity', 15, 3)->unsigned(false)->change();
            $table->decimal('returned_quantity', 15, 3)->default(0)->change();
        });

        // ── 3. sale_return_items ──────────────────────────────────────────────
        Schema::table('sale_return_items', function (Blueprint $table): void {
            $table->decimal('quantity_returned', 15, 3)->unsigned(false)->change();
        });

        // ── 4. inventory_balances ─────────────────────────────────────────────
        Schema::table('inventory_balances', function (Blueprint $table): void {
            $table->decimal('quantity', 15, 3)->default(0)->unsigned(false)->change();
            $table->decimal('reserved_quantity', 15, 3)->default(0)->unsigned(false)->change();
        });

        // ── 5. inventory_movements ────────────────────────────────────────────
        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->decimal('quantity', 15, 3)->unsigned(false)->change();
            $table->decimal('quantity_before', 15, 3)->unsigned(false)->change();
            $table->decimal('quantity_after', 15, 3)->unsigned(false)->change();
            $table->decimal('reserved_before', 15, 3)->nullable()->unsigned(false)->change();
            $table->decimal('reserved_after', 15, 3)->nullable()->unsigned(false)->change();
        });

        // ── 6. inventory_adjustments (tem coluna gerada: difference) ──────────
        // Precisa dropar a coluna gerada antes de alterar as colunas base
        DB::statement('ALTER TABLE inventory_adjustments DROP COLUMN difference');
        Schema::table('inventory_adjustments', function (Blueprint $table): void {
            $table->decimal('previous_quantity', 15, 3)->unsigned(false)->change();
            $table->decimal('new_quantity', 15, 3)->unsigned(false)->change();
        });
        DB::statement('
            ALTER TABLE inventory_adjustments
            ADD COLUMN difference DECIMAL(15,3)
            GENERATED ALWAYS AS (new_quantity - previous_quantity) STORED
        ');

        // ── 7. stock_count_items (tem coluna gerada: difference) ──────────────
        DB::statement('ALTER TABLE stock_count_items DROP COLUMN difference');
        Schema::table('stock_count_items', function (Blueprint $table): void {
            $table->decimal('system_quantity', 15, 3)->unsigned(false)->change();
            $table->decimal('counted_quantity', 15, 3)->nullable()->unsigned(false)->change();
        });
        DB::statement('
            ALTER TABLE stock_count_items
            ADD COLUMN difference DECIMAL(15,3)
            GENERATED ALWAYS AS (counted_quantity - system_quantity) STORED
        ');

        // ── 8. purchase_order_items (tem coluna gerada: total_cost) ───────────
        DB::statement('ALTER TABLE purchase_order_items DROP COLUMN total_cost');
        Schema::table('purchase_order_items', function (Blueprint $table): void {
            $table->decimal('quantity', 15, 3)->unsigned(false)->change();
            $table->decimal('received_quantity', 15, 3)->default(0)->unsigned(false)->change();
        });
        DB::statement('
            ALTER TABLE purchase_order_items
            ADD COLUMN total_cost DECIMAL(15,3)
            GENERATED ALWAYS AS (quantity * unit_cost) STORED
        ');

        // ── 9. purchase_receipt_items ─────────────────────────────────────────
        Schema::table('purchase_receipt_items', function (Blueprint $table): void {
            $table->decimal('quantity_received', 15, 3)->unsigned(false)->change();
        });

        // ── 10. conditional_items ─────────────────────────────────────────────
        Schema::table('conditional_items', function (Blueprint $table): void {
            $table->decimal('quantity', 15, 3)->unsigned(false)->change();
            $table->decimal('returned_quantity', 15, 3)->default(0)->unsigned(false)->change();
            $table->decimal('sold_quantity', 15, 3)->default(0)->unsigned(false)->change();
        });
    }

    public function down(): void
    {
        // ── catalog_products ──────────────────────────────────────────────────
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropColumn('unit_of_measure');
        });

        // ── sale_items ────────────────────────────────────────────────────────
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->integer('quantity')->change();
            $table->unsignedSmallInteger('returned_quantity')->default(0)->change();
        });

        // ── sale_return_items ─────────────────────────────────────────────────
        Schema::table('sale_return_items', function (Blueprint $table): void {
            $table->unsignedSmallInteger('quantity_returned')->change();
        });

        // ── inventory_balances ────────────────────────────────────────────────
        Schema::table('inventory_balances', function (Blueprint $table): void {
            $table->integer('quantity')->default(0)->change();
            $table->integer('reserved_quantity')->default(0)->change();
        });

        // ── inventory_movements ───────────────────────────────────────────────
        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->integer('quantity')->change();
            $table->integer('quantity_before')->change();
            $table->integer('quantity_after')->change();
            $table->integer('reserved_before')->nullable()->change();
            $table->integer('reserved_after')->nullable()->change();
        });

        // ── inventory_adjustments ─────────────────────────────────────────────
        DB::statement('ALTER TABLE inventory_adjustments DROP COLUMN difference');
        Schema::table('inventory_adjustments', function (Blueprint $table): void {
            $table->integer('previous_quantity')->change();
            $table->integer('new_quantity')->change();
        });
        DB::statement('
            ALTER TABLE inventory_adjustments
            ADD COLUMN difference INT
            GENERATED ALWAYS AS (new_quantity - previous_quantity) STORED
        ');

        // ── stock_count_items ─────────────────────────────────────────────────
        DB::statement('ALTER TABLE stock_count_items DROP COLUMN difference');
        Schema::table('stock_count_items', function (Blueprint $table): void {
            $table->integer('system_quantity')->change();
            $table->integer('counted_quantity')->nullable()->change();
        });
        DB::statement('
            ALTER TABLE stock_count_items
            ADD COLUMN difference INT
            GENERATED ALWAYS AS (counted_quantity - system_quantity) STORED
        ');

        // ── purchase_order_items ──────────────────────────────────────────────
        DB::statement('ALTER TABLE purchase_order_items DROP COLUMN total_cost');
        Schema::table('purchase_order_items', function (Blueprint $table): void {
            $table->unsignedInteger('quantity')->change();
            $table->unsignedInteger('received_quantity')->default(0)->change();
        });
        DB::statement('
            ALTER TABLE purchase_order_items
            ADD COLUMN total_cost DECIMAL(12,2)
            GENERATED ALWAYS AS (quantity * unit_cost) STORED
        ');

        // ── purchase_receipt_items ────────────────────────────────────────────
        Schema::table('purchase_receipt_items', function (Blueprint $table): void {
            $table->unsignedInteger('quantity_received')->change();
        });

        // ── conditional_items ─────────────────────────────────────────────────
        Schema::table('conditional_items', function (Blueprint $table): void {
            $table->unsignedInteger('quantity')->change();
            $table->unsignedInteger('returned_quantity')->default(0)->change();
            $table->unsignedInteger('sold_quantity')->default(0)->change();
        });
    }
};
