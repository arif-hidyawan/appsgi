<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            // Tambahkan kolom company_id setelah warehouse_id
            // nullable() diberikan agar data lama tidak error, tapi sebaiknya diisi nanti
            $table->foreignId('company_id')
                ->nullable() 
                ->after('warehouse_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            // Opsional: Tambahkan index untuk mempercepat pencarian stok per perusahaan
            $table->index(['product_id', 'warehouse_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};