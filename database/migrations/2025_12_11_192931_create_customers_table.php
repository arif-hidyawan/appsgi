<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique()->nullable(); // Kode Unik (CUST-001)
            $table->string('name'); // Nama PT/CV
            
            // Kontak Utama Perusahaan
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            
            // Data Pajak / Legal
            $table->string('tax_id')->nullable(); // NPWP
            
            // Alamat (B2B sering membedakan alamat tagihan & kirim)
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            
            // Ketentuan Bisnis
            $table->string('payment_terms')->nullable(); // Net 30, COD
            $table->decimal('credit_limit', 15, 2)->default(0); // Plafon Kredit
            
            $table->boolean('is_active')->default(true); // Status Aktif/Blacklist
            $table->timestamps();
        });
    }
};
