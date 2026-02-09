<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('delivery_orders', function (Blueprint $table) {
        $table->string('bast_number')->nullable()->after('driver_name');
        $table->string('attachment')->nullable()->after('bast_number'); // Untuk path foto
    });
}

public function down(): void
{
    Schema::table('delivery_orders', function (Blueprint $table) {
        $table->dropColumn(['bast_number', 'attachment']);
    });
}
};
