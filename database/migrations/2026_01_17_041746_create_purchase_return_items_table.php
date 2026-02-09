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
    Schema::create('purchase_return_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('purchase_return_id')->constrained()->cascadeOnDelete();
        $table->foreignId('product_id')->constrained();
        $table->integer('qty');
        $table->string('reason')->nullable(); // Rusak, Salah Kirim, dll
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
