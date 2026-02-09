<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('rfqs', function (Blueprint $table) {
        // Relasi ke tabel Users (Sales Staff)
        $table->foreignId('sales_id')->nullable()->constrained('users')->onDelete('set null');
        
        // Status RFQ
        $table->string('status')->default('Draft'); // Draft, Disetujui, Selesai
    });
}

public function down(): void
{
    Schema::table('rfqs', function (Blueprint $table) {
        $table->dropForeign(['sales_id']);
        $table->dropColumn(['sales_id', 'status']);
    });
}
};
