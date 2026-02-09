<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            // Hapus indeks lama yang salah
            $table->dropUnique(['product_id', 'warehouse_id']);
            
            // Buat indeks baru yang menyertakan company_id
            $table->unique(['product_id', 'warehouse_id', 'company_id'], 'product_stocks_full_unique');
        });
    }
    
    public function down(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropUnique('product_stocks_full_unique');
            $table->unique(['product_id', 'warehouse_id']);
        });
    }
};
