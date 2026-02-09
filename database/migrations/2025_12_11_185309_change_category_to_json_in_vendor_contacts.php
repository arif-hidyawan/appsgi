<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
{
    // LANGKAH 1: Perbaiki data yang ada agar menjadi format JSON yang valid
    // Contoh: Mengubah "Elektronik" menjadi '["Elektronik"]'
    
    // Update data kosong string ('') menjadi NULL dulu
    DB::statement("UPDATE vendor_contacts SET category = NULL WHERE category = ''");

    // Bungkus data teks yang ada menjadi array JSON: ["IsiCategory"]
    DB::statement("
        UPDATE vendor_contacts 
        SET category = CONCAT('[\"', category, '\"]') 
        WHERE category IS NOT NULL
    ");

    // LANGKAH 2: Ubah tipe kolom (Sekarang aman karena datanya sudah valid)
    Schema::table('vendor_contacts', function (Blueprint $table) {
        $table->json('category')->nullable()->change();
    });
}
    
    public function down(): void
    {
        Schema::table('vendor_contacts', function (Blueprint $table) {
            $table->string('category')->nullable()->change();
        });
    }
};
