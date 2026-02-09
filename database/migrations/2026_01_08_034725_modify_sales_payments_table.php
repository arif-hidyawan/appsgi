<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales_payments', function (Blueprint $table) {
            // Tambah flag perusahaan dan akun Kas/Bank yang menerima uang
            $table->foreignId('company_id')->after('id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('account_id')->after('sales_invoice_id')->nullable()->constrained('accounts'); 
        });
    }

    public function down(): void
    {
        Schema::table('sales_payments', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['account_id']);
            $table->dropColumn(['company_id', 'account_id']);
        });
    }
};