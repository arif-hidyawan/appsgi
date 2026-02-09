<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        // Identitas Item
        $table->string('item_code')->unique(); // Kode Internal
        $table->string('sku')->nullable()->unique(); // SKU Pabrik/Global
        $table->string('barcode')->nullable(); // Scan Barcode
        $table->string('name');
        
        // Relasi & Kategori
        $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
        $table->string('category')->nullable(); // Bisa ubah jadi relation jika punya master kategori
        
        // Fisik
        $table->string('image')->nullable();
        $table->text('description')->nullable();
        $table->string('unit')->default('Pcs'); // Satuan: Pcs, Box, Kg
        $table->integer('weight_gram')->default(0)->nullable(); // Berat untuk ongkir
        
        // Harga (Tiering B2B)
        $table->decimal('cost_price', 15, 2)->default(0); // HPP
        $table->decimal('price_1', 15, 2)->default(0); // Harga Retail / Ecer
        $table->decimal('price_2', 15, 2)->default(0); // Harga Grosir / Reseller
        $table->decimal('price_3', 15, 2)->default(0); // Harga Distributor / Partai Besar
        
        // Inventory
        $table->integer('stock')->default(0);
        $table->integer('min_stock')->default(5); // Alert jika stok < 5
        
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}
};
