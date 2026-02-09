<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Header Quotation
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->date('date');
            
            // Relasi ke Customer & Sales
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('sales_id')->nullable()->constrained('users');
            
            // Relasi ke RFQ (Nullable, karena bisa buat Quotation tanpa RFQ)
            $table->foreignId('rfq_id')->nullable()->constrained('rfqs')->onDelete('set null');

            $table->string('status')->default('Draft'); // Draft, Sent, Accepted, Rejected
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });

        // 2. Detail Item Quotation
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            
            $table->integer('qty');
            $table->decimal('unit_price', 15, 2); // Harga Jual Satuan
            $table->decimal('subtotal', 15, 2);   // Qty * Unit Price
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};