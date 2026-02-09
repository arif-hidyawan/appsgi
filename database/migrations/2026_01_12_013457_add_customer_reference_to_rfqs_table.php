<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            // Menambahkan kolom string (text pendek) setelah rfq_number
            // Nullable agar tidak error jika data lama kosong
            $table->string('customer_reference')->nullable()->after('rfq_number')->comment('Input teks untuk bukti/ref customer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropColumn('customer_reference');
        });
    }
};