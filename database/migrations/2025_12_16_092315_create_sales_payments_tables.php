<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique(); // No. Kwitansi
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices');
            
            $table->date('date');
            $table->decimal('amount', 15, 2); // Jumlah dibayar
            $table->string('payment_method'); // Transfer BCA, Mandiri, Cash, Cek
            $table->string('reference_number')->nullable(); // No Bukti Transfer
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_payments');
    }
};