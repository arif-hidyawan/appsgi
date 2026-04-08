<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('journals', function (Blueprint $table) {
        $table->string('contact_name', 150)->nullable()->after('sales_name')->comment('Pihak ke-3 / Pengirim / Penerima Dana');
    });
}

public function down(): void
{
    Schema::table('journals', function (Blueprint $table) {
        $table->dropColumn('contact_name');
    });
}
};
