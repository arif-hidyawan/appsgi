<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Header PO
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->date('date');
            
            // Relasi ke Vendor & SO (Opsional, untuk tracking ini PO buat SO nomor berapa)
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders'); 
            
            $table->string('status')->default('Draft'); // Draft, Ordered, Received, Cancelled
            $table->decimal('grand_total', 15, 2)->default(0);
            
            $table->timestamps();
        });

        // 2. Detail Item PO
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            
            $table->integer('qty');
            $table->decimal('unit_price', 15, 2); // Harga Beli (HPP)
            $table->decimal('subtotal', 15, 2);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};