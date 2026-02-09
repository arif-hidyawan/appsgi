<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tambah kolom di tabel vendors
        Schema::table('vendors', function (Blueprint $table) {
            $table->date('company_anniversary')->nullable()->after('address');
        });
    
        // Tambah kolom di tabel vendor_contacts
        Schema::table('vendor_contacts', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('phone'); // Sesuaikan 'after' dengan kolom terakhirmu
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors_and_contacts', function (Blueprint $table) {
            //
        });
    }
};
