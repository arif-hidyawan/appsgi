<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            // Tambah kolom vendor_id (Nullable, karena mungkin ada item manual tanpa vendor)
            $table->foreignId('vendor_id')
                  ->nullable()
                  ->after('product_id')
                  ->constrained('vendors')
                  ->nullOnDelete();

            // Tambah kolom cost_price (HPP)
            $table->decimal('cost_price', 15, 2)
                  ->default(0)
                  ->after('qty');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropColumn(['vendor_id', 'cost_price']);
        });
    }
};