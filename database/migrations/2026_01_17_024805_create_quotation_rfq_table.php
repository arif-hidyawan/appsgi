<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_rfq', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rfq_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    
        // Opsional: Migrasikan data lama (jika ada data rfq_id di quotations)
        // DB::statement("INSERT INTO quotation_rfq (quotation_id, rfq_id) SELECT id, rfq_id FROM quotations WHERE rfq_id IS NOT NULL");
    
        // Hapus kolom rfq_id lama di quotations
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropForeign(['rfq_id']);
            $table->dropColumn('rfq_id');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_rfq');
    }
};
