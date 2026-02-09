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
    Schema::table('goods_receive_items', function (Blueprint $table) {
        $table->integer('qty_rejected')->default(0)->after('qty_received');
        $table->string('rejection_reason')->nullable()->after('qty_rejected');
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receive_items', function (Blueprint $table) {
            //
        });
    }
};
