<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    // 1. Tambah Anniversary ke tabel Customers
    Schema::table('customers', function (Blueprint $table) {
        $table->date('anniversary_date')->nullable()->after('name');
    });

    // 2. Buat tabel Customer Contacts (PIC)
    Schema::create('customer_contacts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
        $table->string('pic_name');
        $table->string('phone')->nullable();
        $table->string('email')->nullable();
        $table->date('birth_date')->nullable(); // Ulang tahun PIC
        $table->string('position')->nullable(); // Jabatan
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_contacts_and_update_customers');
    }
};
