<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Header Sales Order
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('so_number')->unique();
            $table->date('date');
            
            // Relasi
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('quotation_id')->nullable()->constrained('quotations'); // Link ke Quotation
            $table->foreignId('sales_id')->nullable()->constrained('users'); // Sales yg handle
            
            $table->string('customer_po_number')->nullable(); // Nomor PO dari Customer (Penting!)
            $table->string('status')->default('New'); // New, Processed, Completed, Cancelled
            
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });

        // 2. Detail Item Sales Order
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            
            $table->integer('qty');
            $table->decimal('unit_price', 15, 2); // Harga Jual Deal
            $table->decimal('subtotal', 15, 2);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
    }
};