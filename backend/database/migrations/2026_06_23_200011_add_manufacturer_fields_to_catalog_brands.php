<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona campos de fabricante à tabela catalog_brands.
 * Em material de construção, fabricante e marca costumam ser a mesma entidade.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_brands', function (Blueprint $table): void {
            $table->string('manufacturer_cnpj', 20)->nullable()->after('website_url');
            $table->string('manufacturer_contact_name', 150)->nullable()->after('manufacturer_cnpj');
            $table->string('manufacturer_contact_email', 254)->nullable()->after('manufacturer_contact_name');
            $table->string('manufacturer_contact_phone', 30)->nullable()->after('manufacturer_contact_email');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_brands', function (Blueprint $table): void {
            $table->dropColumn([
                'manufacturer_cnpj',
                'manufacturer_contact_name',
                'manufacturer_contact_email',
                'manufacturer_contact_phone',
            ]);
        });
    }
};
