<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Header Tagihan Vendor
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique(); // No Invoice dari Vendor
            $table->date('date'); // Tanggal Invoice
            $table->date('due_date'); // Jatuh Tempo
            
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->foreignId('vendor_id')->constrained('vendors');
            
            $table->decimal('grand_total', 15, 2);
            $table->string('status')->default('Unpaid'); // Unpaid, Paid
            
            // File bukti tagihan fisik (scan)
            $table->string('attachment')->nullable(); 
            
            $table->timestamps();
        });

        // 2. Detail Item Tagihan
        Schema::create('purchase_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            
            $table->integer('qty');
            $table->decimal('unit_price', 15, 2); // Harga Beli Final
            $table->decimal('subtotal', 15, 2);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_items');
        Schema::dropIfExists('purchase_invoices');
    }
};