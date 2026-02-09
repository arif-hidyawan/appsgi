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
        Schema::table('rfq_items', function (Blueprint $table) {
            // Menambahkan kolom notes tipe TEXT, boleh kosong (nullable)
            // diletakkan setelah kolom selling_price agar rapi
            $table->text('notes')->nullable()->after('selling_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfq_items', function (Blueprint $table) {
            // Hapus kolom jika rollback
            $table->dropColumn('notes');
        });
    }
};