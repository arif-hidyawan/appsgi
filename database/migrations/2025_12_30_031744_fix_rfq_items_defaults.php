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
        Schema::table('rfq_items', function (Blueprint $table) {
            // 1. Ubah cost_price agar punya DEFAULT 0 (Solusi error: Field doesn't have a default value)
            $table->decimal('cost_price', 15, 2)->default(0)->change();
            
            // 2. Ubah selling_price agar punya DEFAULT 0
            $table->decimal('selling_price', 15, 2)->default(0)->change();
            
            // 3. Ubah vendor_id agar BOLEH NULL (Nullable)
            // Kita drop foreign key dulu (opsional, tergantung config DB), lalu ubah jadi nullable
            // Jika error foreign key, coba baris $table->unsignedBigInteger('vendor_id')->nullable()->change(); saja
            $table->foreignId('vendor_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfq_items', function (Blueprint $table) {
            // Kembalikan ke pengaturan awal (Strict)
            // Hati-hati, ini bisa error jika ada data yang sudah terlanjur 0 atau null
            // $table->decimal('cost_price', 15, 2)->default(null)->change();
            // $table->decimal('selling_price', 15, 2)->default(null)->change();
            // $table->foreignId('vendor_id')->nullable(false)->change();
        });
    }
};