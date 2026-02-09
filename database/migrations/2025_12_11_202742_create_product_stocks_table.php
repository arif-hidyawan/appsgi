<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('product_stocks', function (Blueprint $table) {
        $table->id();
        
        // Relasi ke Produk
        $table->foreignId('product_id')
              ->constrained('products')
              ->cascadeOnDelete(); // Jika produk dihapus, stok hilang
              
        // Relasi ke Gudang
        $table->foreignId('warehouse_id')
              ->constrained('warehouses')
              ->cascadeOnDelete();
              
        // Jumlah Stok di gudang tersebut
        $table->integer('quantity')->default(0);
        
        $table->timestamps();

        // Mencegah duplikasi: 1 Produk tidak boleh ada 2 baris untuk gudang yang sama
        $table->unique(['product_id', 'warehouse_id']);
    });
}
};
