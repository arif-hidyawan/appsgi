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
    $tables = ['rfqs', 'quotations', 'sales_orders']; // 3 Tabel Transaksi Utama

    foreach ($tables as $table) {
        Schema::table($table, function (Blueprint $table) {
            // Kolom Subtotal (Total Barang Saja)
            $table->decimal('subtotal_amount', 15, 2)->default(0)->after('status');

            // Kolom Nominal Pajak
            $table->decimal('tax_amount', 15, 2)->default(0)->after('subtotal_amount');

            // Pastikan grand_total sudah ada (biasanya sudah), kalau belum, uncomment ini:
            // $table->decimal('grand_total', 15, 2)->default(0)->after('tax_amount');
        });
    }
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            //
        });
    }
};
