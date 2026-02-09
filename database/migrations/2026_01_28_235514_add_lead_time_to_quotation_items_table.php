<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('quotation_items', function (Blueprint $table) {
        $table->integer('lead_time')->default(0)->after('qty'); 
    });
}

public function down()
{
    Schema::table('quotation_items', function (Blueprint $table) {
        $table->dropColumn('lead_time');
    });
}
};
