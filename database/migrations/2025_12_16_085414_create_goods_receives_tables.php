<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Header Penerimaan
        Schema::create('goods_receives', function (Blueprint $table) {
            $table->id();
            $table->string('gr_number')->unique(); // Nomor Surat Jalan Masuk
            $table->date('date');
            
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->foreignId('vendor_id')->constrained('vendors'); // Redundan tapi perlu utk query cepat
            
            $table->string('vendor_delivery_number')->nullable(); // No. Surat Jalan dari Vendor
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });

        // 2. Detail Barang Masuk
        Schema::create('goods_receive_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receive_id')->constrained('goods_receives')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            
            $table->integer('qty_ordered'); // Qty di PO
            $table->integer('qty_received'); // Qty Fisik yang diterima (Bisa kurang/lebih)
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receive_items');
        Schema::dropIfExists('goods_receives');
    }
};