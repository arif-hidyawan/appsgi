<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique(); // No. Bukti Keluar
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices');
            
            $table->date('date');
            $table->decimal('amount', 15, 2); // Jumlah dibayar
            $table->string('payment_method'); // Transfer, Cash, Cek
            $table->string('reference_number')->nullable(); // No Ref Bank
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
    }
};