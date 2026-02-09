<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel Utama Mutasi
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique();
            $table->date('date');
            
            // Asal
            $table->foreignId('source_company_id')->constrained('companies');
            $table->foreignId('source_warehouse_id')->constrained('warehouses');
            
            // Tujuan
            $table->foreignId('destination_company_id')->constrained('companies');
            $table->foreignId('destination_warehouse_id')->constrained('warehouses');
            
            $table->string('status')->default('Draft'); // Draft, Completed
            $table->text('notes')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // Tabel Detail Barang Mutasi (Ini yang error tadi)
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->integer('qty');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};