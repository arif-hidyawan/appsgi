<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('sales_payments', function (Blueprint $table) {
        // Cek dulu biar aman, kalau belum ada baru dibuat
        if (!Schema::hasColumn('sales_payments', 'customer_id')) {
            $table->foreignId('customer_id')
                  ->nullable()
                  ->after('company_id') // Posisi setelah company_id
                  ->constrained('customers')
                  ->nullOnDelete();
        }
    });
}

public function down(): void
{
    Schema::table('sales_payments', function (Blueprint $table) {
        $table->dropForeign(['customer_id']);
        $table->dropColumn('customer_id');
    });
}
};
