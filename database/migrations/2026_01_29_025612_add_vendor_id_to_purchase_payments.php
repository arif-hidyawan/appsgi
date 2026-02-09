<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('purchase_payments', function (Blueprint $table) {
        if (!Schema::hasColumn('purchase_payments', 'vendor_id')) {
            $table->foreignId('vendor_id')
                  ->nullable()
                  ->after('company_id')
                  ->constrained('vendors') // Relasi ke tabel vendors
                  ->nullOnDelete();
        }
    });
}

public function down(): void
{
    Schema::table('purchase_payments', function (Blueprint $table) {
        $table->dropForeign(['vendor_id']);
        $table->dropColumn('vendor_id');
    });
}
};
