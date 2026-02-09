<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Informasi Vendor / Nama Perusahaan
            $table->string('category')->nullable(); // Kategori
            $table->string('pic_name')->nullable(); // PIC
            $table->string('phone')->nullable(); // Kontak
            $table->string('email')->nullable(); // Email
            $table->text('address')->nullable(); // Alamat
            $table->string('vendor_response')->nullable(); // Respon Vendor
            $table->string('payment_terms')->nullable(); // Termin Pembayaran
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};