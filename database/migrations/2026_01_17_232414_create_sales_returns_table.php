<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Header Retur
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->date('date');
            $table->foreignId('delivery_order_id')->constrained();
            $table->foreignId('sales_order_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('company_id')->constrained();
            $table->text('reason')->nullable();
            $table->string('status')->default('Draft'); // Draft, Approved
            $table->timestamps();
        });
    
        // Item Retur
        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->integer('qty');
            $table->string('condition')->default('Good'); // Good (Bisa dijual lagi), Bad (Rusak)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};
