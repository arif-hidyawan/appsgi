<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('taxes', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // Contoh: PPN 11%, PPh 23
        $table->decimal('rate', 5, 2); // Contoh: 11.00 (Persentase)
        $table->integer('priority')->default(0); // Urutan prioritas (Opsional)
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
