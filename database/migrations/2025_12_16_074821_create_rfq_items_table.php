<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfq_items', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke Header RFQ dan Produk
            $table->foreignId('rfq_id')->constrained('rfqs')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');

            $table->unsignedInteger('qty');
            $table->decimal('cost_price', 15, 2); // HPP yang ditentukan untuk RFQ ini
            $table->decimal('selling_price', 15, 2); // Harga Jual Target untuk Quotation

            // Relasi ke Vendor (Jika Vendor Terpilih sudah ada saat RFQ dibuat)
            $table->foreignId('vendor_id')->nullable()->constrained('vendors'); 

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_items');
    }
};