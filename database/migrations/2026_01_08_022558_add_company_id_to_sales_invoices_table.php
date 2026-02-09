<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cek dulu apakah FK sudah ada sebelum pasang, biar tidak "Bangsat" lagi errornya
        $exists = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                              WHERE TABLE_NAME = 'sales_invoices' 
                              AND CONSTRAINT_NAME = 'sales_invoices_company_id_foreign' 
                              AND TABLE_SCHEMA = DATABASE()");

        Schema::table('sales_invoices', function (Blueprint $table) use ($exists) {
            // Jika kolom belum ada, buat kolomnya
            if (!Schema::hasColumn('sales_invoices', 'company_id')) {
                $table->foreignId('company_id')->after('id')->nullable();
            }

            // Jika Foreign Key belum ada, baru pasang
            if (empty($exists)) {
                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            // Hapus FK dulu baru hapus kolom
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};