<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    // Cek dulu apakah kolom sudah ada (untuk mencegah error double column)
    if (!Schema::hasColumn('products', 'stock_classification_id')) {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('stock_classification_id')
                  ->nullable()
                  ->constrained('stock_classifications')
                  ->nullOnDelete();
        });
    }
}

public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropForeign(['stock_classification_id']);
        $table->dropColumn('stock_classification_id');
    });
}
};
