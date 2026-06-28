<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            // Classificação e perfil fiscal
            if (! Schema::hasColumn('customers', 'is_final_consumer')) {
                $table->boolean('is_final_consumer')->default(true)->after('is_default_consumer');
            }
            if (! Schema::hasColumn('customers', 'is_free_zone')) {
                $table->boolean('is_free_zone')->default(false);
            }
            if (! Schema::hasColumn('customers', 'is_store_chain')) {
                $table->boolean('is_store_chain')->default(false);
            }
            if (! Schema::hasColumn('customers', 'is_public_entity')) {
                $table->boolean('is_public_entity')->default(false);
            }

            // Vínculos comerciais
            if (! Schema::hasColumn('customers', 'representative_id')) {
                $table->foreignUuid('representative_id')->nullable()->after('seller_id')
                    ->constrained('seller_profiles', 'uuid')->nullOnDelete();
            }
            if (! Schema::hasColumn('customers', 'collection_bank_id')) {
                $table->foreignUuid('collection_bank_id')->nullable()
                    ->constrained('financial_accounts', 'uuid')->nullOnDelete();
            }

            // Dados complementares
            if (! Schema::hasColumn('customers', 'website')) {
                $table->string('website', 200)->nullable();
            }
            if (! Schema::hasColumn('customers', 'contact_name')) {
                $table->string('contact_name', 150)->nullable();
            }
            if (! Schema::hasColumn('customers', 'postal_box')) {
                $table->string('postal_box', 20)->nullable();
            }
            if (! Schema::hasColumn('customers', 'economic_activity')) {
                $table->string('economic_activity', 150)->nullable();
            }
            if (! Schema::hasColumn('customers', 'capital_stock_cents')) {
                $table->unsignedBigInteger('capital_stock_cents')->nullable();
            }

            // Retenções fiscais PJ
            if (! Schema::hasColumn('customers', 'withholds_pis_cofins')) {
                $table->boolean('withholds_pis_cofins')->default(false);
            }
            if (! Schema::hasColumn('customers', 'withholds_irpj')) {
                $table->boolean('withholds_irpj')->default(false);
            }
            if (! Schema::hasColumn('customers', 'withholds_iss')) {
                $table->boolean('withholds_iss')->default(false);
            }
            if (! Schema::hasColumn('customers', 'iss_rate')) {
                $table->decimal('iss_rate', 5, 2)->default(0);
            }
            if (! Schema::hasColumn('customers', 'withholds_social_security')) {
                $table->boolean('withholds_social_security')->default(false);
            }
            if (! Schema::hasColumn('customers', 'calculates_icms_discount')) {
                $table->boolean('calculates_icms_discount')->default(false);
            }
            if (! Schema::hasColumn('customers', 'discount_type')) {
                $table->string('discount_type', 50)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('representative_id');
            $table->dropConstrainedForeignId('collection_bank_id');
            $table->dropColumn([
                'is_final_consumer', 'is_free_zone', 'is_store_chain', 'is_public_entity',
                'website', 'contact_name', 'postal_box', 'economic_activity', 'capital_stock_cents',
                'withholds_pis_cofins', 'withholds_irpj', 'withholds_iss', 'iss_rate',
                'withholds_social_security', 'calculates_icms_discount', 'discount_type',
            ]);
        });
    }
};
