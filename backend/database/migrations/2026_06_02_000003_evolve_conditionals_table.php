<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Alter conditionals table ──────────────────────────────────────────
        Schema::table('conditionals', function (Blueprint $table): void {
            // Add opened_by after customer_id
            $table->uuid('opened_by')->nullable()->after('customer_id');

            // Drop unneeded columns
            $table->dropColumn(['discount_total_cents', 'metadata']);

            // Add total_items after notes
            $table->unsignedInteger('total_items')->default(0)->after('notes');

            // Foreign key for opened_by
            $table->foreign('opened_by')
                ->references('uuid')
                ->on('users')
                ->nullOnDelete();
        });

        // ── Create conditional_items table ────────────────────────────────────
        Schema::create('conditional_items', function (Blueprint $table): void {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('conditional_id');
            $table->uuid('variant_id');
            $table->unsignedInteger('quantity');
            $table->integer('unit_price_cents');
            $table->unsignedInteger('returned_quantity')->default(0);
            $table->unsignedInteger('sold_quantity')->default(0);
            $table->timestamps();

            $table->foreign('conditional_id')
                ->references('uuid')
                ->on('conditionals')
                ->cascadeOnDelete();

            $table->foreign('variant_id')
                ->references('uuid')
                ->on('catalog_variants')
                ->restrictOnDelete();

            $table->index('conditional_id');
        });

        // ── Create conditional_status_history table ───────────────────────────
        Schema::create('conditional_status_history', function (Blueprint $table): void {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('conditional_id');
            $table->string('previous_status', 30)->nullable();
            $table->string('current_status', 30);
            $table->uuid('changed_by')->nullable();
            $table->timestamp('changed_at')->useCurrent();
            // No updated_at — model sets UPDATED_AT = null

            $table->foreign('conditional_id')
                ->references('uuid')
                ->on('conditionals')
                ->cascadeOnDelete();

            $table->foreign('changed_by')
                ->references('uuid')
                ->on('users')
                ->nullOnDelete();

            $table->index(['conditional_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conditional_status_history');
        Schema::dropIfExists('conditional_items');

        Schema::table('conditionals', function (Blueprint $table): void {
            $table->dropForeign(['opened_by']);
            $table->dropColumn(['opened_by', 'total_items']);
            $table->unsignedBigInteger('discount_total_cents')->default(0);
            $table->jsonb('metadata')->nullable();
        });
    }
};
