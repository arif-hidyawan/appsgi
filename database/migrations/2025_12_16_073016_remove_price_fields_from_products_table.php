<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Hapus kolom harga
            $table->dropColumn(['cost_price', 'price_1', 'price_2', 'price_3']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Tambahkan kembali kolom harga jika migration di-rollback
            $table->decimal('cost_price', 15, 2)->default(0)->after('weight_gram');
            $table->decimal('price_1', 15, 2)->default(0)->after('cost_price');
            $table->decimal('price_2', 15, 2)->default(0)->after('price_1');
            $table->decimal('price_3', 15, 2)->default(0)->after('price_2');
        });
    }
};