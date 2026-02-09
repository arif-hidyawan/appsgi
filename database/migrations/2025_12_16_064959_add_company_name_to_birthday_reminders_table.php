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
        Schema::table('birthday_reminders', function (Blueprint $table) {
            // Tambahkan kolom company_name, nullable karena hanya diisi untuk PIC
            $table->string('company_name')->nullable()->after('type'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('birthday_reminders', function (Blueprint $table) {
            // Hapus kolom saat rollback
            $table->dropColumn('company_name'); 
        });
    }
};