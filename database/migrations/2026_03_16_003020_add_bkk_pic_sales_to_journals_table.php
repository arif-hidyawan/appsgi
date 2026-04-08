<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('journals', function (Blueprint $table) {
        $table->string('bkk_number', 100)->nullable()->after('reference')->comment('Nomor BKK/BBM');
        // Asumsi PIC ditarik dari tabel users
        $table->foreignId('pic_id')->nullable()->after('bkk_number')->constrained('users')->nullOnDelete();
        // Asumsi Sales diketik manual atau ditarik dari string (jika ada tabel khusus, ganti jadi foreignId)
        $table->string('sales_name', 100)->nullable()->after('pic_id');
    });
}

public function down(): void
{
    Schema::table('journals', function (Blueprint $table) {
        $table->dropForeign(['pic_id']);
        $table->dropColumn(['bkk_number', 'pic_id', 'sales_name']);
    });
}
};
