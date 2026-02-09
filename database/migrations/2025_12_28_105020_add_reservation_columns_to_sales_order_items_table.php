<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            // Kolom untuk menyimpan ID Gudang tempat stok dikunci
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('subtotal') // Menaruh kolom setelah subtotal (opsional, biar rapi)
                ->constrained('warehouses')
                ->nullOnDelete(); // Jika gudang dihapus, reservasi lepas

            // Penanda waktu kapan stok dikunci (juga berfungsi sebagai flag is_reserved)
            $table->timestamp('reserved_at')
                ->nullable()
                ->after('warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn(['warehouse_id', 'reserved_at']);
        });
    }
};