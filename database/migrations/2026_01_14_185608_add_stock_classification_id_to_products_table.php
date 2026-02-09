<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('stock_classification_id')
                  ->nullable() // Boleh kosong dulu
                  ->constrained('stock_classifications')
                  ->nullOnDelete();
        });
    }
    
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['stock_classification_id']);
            $table->dropColumn('stock_classification_id');
        });
    }
};
