<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        // 1. Hapus kolom unit lama (string)
        // Hati-hati: Data unit lama akan hilang jika belum di-backup
        $table->dropColumn('unit');

        // 2. Tambah kolom relasi unit_id
        $table->foreignId('unit_id')
              ->nullable() // Boleh null dulu saat migrasi
              ->after('category_id') // Letakkan setelah kategori
              ->constrained('units')
              ->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropForeign(['unit_id']);
        $table->dropColumn('unit_id');
        $table->string('unit')->default('Pcs');
    });
}
};
