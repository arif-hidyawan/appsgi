<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Header Surat Jalan
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('do_number')->unique(); // Nomor Surat Jalan
            $table->date('date');
            
            $table->foreignId('sales_order_id')->constrained('sales_orders');
            $table->foreignId('customer_id')->constrained('customers'); 
            
            $table->string('vehicle_number')->nullable(); // Plat Nomor Kendaraan
            $table->string('driver_name')->nullable();    // Nama Supir
            $table->text('notes')->nullable();
            
            $table->string('status')->default('Draft'); // Draft, Sent, Delivered
            $table->timestamps();
        });

        // 2. Detail Barang Keluar
        Schema::create('delivery_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            
            $table->integer('qty_ordered'); // Qty di SO
            $table->integer('qty_delivered'); // Qty yang dikirim (Stock Out)
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_items');
        Schema::dropIfExists('delivery_orders');
    }
};