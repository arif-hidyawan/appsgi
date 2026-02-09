<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('birthday_reminders', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama Vendor atau PIC
            $table->string('type'); // 'Perusahaan' atau 'PIC'
            $table->date('date'); // Tanggal Lahir/Anniversary
            $table->string('status')->default('Belum Dikirim'); // Belum Dikirim, Sudah Dikirim
            $table->string('proof')->nullable(); // Foto Bukti
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('birthday_reminders');
    }
};
