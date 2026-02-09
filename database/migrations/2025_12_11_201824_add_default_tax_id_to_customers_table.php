<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('customers', function (Blueprint $table) {
        // Tambahkan kolom default_tax_id setelah kolom tax_id (NPWP)
        $table->foreignId('default_tax_id')
              ->nullable()
              ->after('tax_id') 
              ->constrained('taxes')
              ->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('customers', function (Blueprint $table) {
        $table->dropForeign(['default_tax_id']);
        $table->dropColumn('default_tax_id');
    });
}
};
