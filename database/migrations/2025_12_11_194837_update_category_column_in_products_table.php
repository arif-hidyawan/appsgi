<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        // 1. Hapus kolom category lama (string)
        // Pastikan backup data dulu jika sudah ada isinya!
        $table->dropColumn('category');

        // 2. Tambah kolom baru category_id (relasi)
        $table->foreignId('category_id')
              ->nullable()
              ->after('name')
              ->constrained('categories')
              ->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropForeign(['category_id']);
        $table->dropColumn('category_id');
        $table->string('category')->nullable();
    });
}
};
