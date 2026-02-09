<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('rfq_items', function (Blueprint $table) {
        // 0 = Ready Stock, Angka > 0 = Jumlah Hari Inden
        $table->integer('lead_time')->default(0)->after('qty'); 
    });
}

public function down()
{
    Schema::table('rfq_items', function (Blueprint $table) {
        $table->dropColumn('lead_time');
    });
}
};
