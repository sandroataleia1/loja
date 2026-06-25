<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_contacts', function (Blueprint $table): void {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('supplier_id')->constrained('suppliers', 'uuid')->cascadeOnDelete();
            $table->string('type', 20); // PHONE|WHATSAPP|EMAIL|OTHER
            $table->string('value', 200);
            $table->string('label', 100)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_contacts');
    }
};
