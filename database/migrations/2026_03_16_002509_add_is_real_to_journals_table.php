<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('journals', function (Blueprint $table) {
        // Secara default bernilai true (Real). False berarti Semu.
        $table->boolean('is_real')->default(true)->after('reference')->comment('1 = Real, 0 = Semu');
    });
}

public function down(): void
{
    Schema::table('journals', function (Blueprint $table) {
        $table->dropColumn('is_real');
    });
}
};
