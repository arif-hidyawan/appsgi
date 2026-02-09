<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('sales_invoices', function (Blueprint $table) {
        // Tambahkan kolom subtotal dan tax
        if (!Schema::hasColumn('sales_invoices', 'subtotal_amount')) {
            $table->decimal('subtotal_amount', 15, 2)->default(0)->after('company_id');
        }
        if (!Schema::hasColumn('sales_invoices', 'tax_amount')) {
            $table->decimal('tax_amount', 15, 2)->default(0)->after('subtotal_amount');
        }
    });
}

public function down(): void
{
    Schema::table('sales_invoices', function (Blueprint $table) {
        $table->dropColumn(['subtotal_amount', 'tax_amount']);
    });
}
};
