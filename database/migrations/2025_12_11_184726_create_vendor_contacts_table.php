<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buat Table Baru
        Schema::create('vendor_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('pic_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('category')->nullable();
            $table->text('vendor_info')->nullable();
            $table->timestamps();
        });

        // 2. Tambah kolom vendor_code di vendors
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('vendor_code')->nullable()->after('id')->unique();
        });

        // 3. (Opsional) Migrasi Data Lama ke Table Baru
        // Ini memindahkan data PIC yang sudah ada di vendors ke vendor_contacts
        DB::statement("
            INSERT INTO vendor_contacts (vendor_id, pic_name, phone, email, category, vendor_info, created_at, updated_at)
            SELECT id, pic_name, phone, email, category, vendor_info, NOW(), NOW()
            FROM vendors
        ");

        // 4. Hapus kolom lama dari vendors agar bersih
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['pic_name', 'phone', 'email', 'category', 'vendor_info']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_contacts');
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('vendor_code');
            $table->string('pic_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('category')->nullable();
            $table->text('vendor_info')->nullable();
        });
    }
};