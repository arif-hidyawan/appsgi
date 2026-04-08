<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('numbering_templates', function (Blueprint $table) {
        $table->id();
        $table->foreignId('company_id')->constrained()->cascadeOnDelete();
        $table->string('source')->comment('Kas Masuk, Kas Keluar, Jurnal Umum, Transfer Kas');
        $table->string('format')->comment('Contoh: 1 {m}.{y}/AB-K{num}');
        $table->integer('pad_length')->default(2)->comment('Panjang nol di depan nomor, misal 2 jadi 06');
        $table->integer('last_sequence')->default(0)->comment('Nomor urut terakhir');
        $table->timestamps();
        
        // Mencegah ada 2 template yang sama untuk 1 jenis transaksi di PT yang sama
        $table->unique(['company_id', 'source']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('numbering_templates');
    }
};
