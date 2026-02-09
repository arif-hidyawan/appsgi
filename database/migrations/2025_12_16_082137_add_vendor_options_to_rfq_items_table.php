<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfq_items', function (Blueprint $table) {
            // Opsi 1
            $table->foreignId('vendor_1_id')->nullable()->constrained('vendors');
            $table->decimal('price_1', 15, 2)->nullable();
            
            // Opsi 2
            $table->foreignId('vendor_2_id')->nullable()->constrained('vendors');
            $table->decimal('price_2', 15, 2)->nullable();

            // Opsi 3
            $table->foreignId('vendor_3_id')->nullable()->constrained('vendors');
            $table->decimal('price_3', 15, 2)->nullable();

            // Pilihan Admin (1, 2, atau 3)
            $table->tinyInteger('selected_option')->nullable()->comment('1, 2, or 3');
        });
    }

    public function down(): void
    {
        Schema::table('rfq_items', function (Blueprint $table) {
            $table->dropForeign(['vendor_1_id']);
            $table->dropForeign(['vendor_2_id']);
            $table->dropForeign(['vendor_3_id']);
            $table->dropColumn(['vendor_1_id', 'price_1', 'vendor_2_id', 'price_2', 'vendor_3_id', 'price_3', 'selected_option']);
        });
    }
};