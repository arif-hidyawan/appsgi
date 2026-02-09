<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('quotation_sales_order', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
        $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
        $table->timestamps();
    });

    // Hapus kolom quotation_id lama di sales_orders (Opsional, atau biarkan null)
    Schema::table('sales_orders', function (Blueprint $table) {
        $table->dropForeign(['quotation_id']);
        $table->dropColumn('quotation_id');
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_sales_order');
    }
};
