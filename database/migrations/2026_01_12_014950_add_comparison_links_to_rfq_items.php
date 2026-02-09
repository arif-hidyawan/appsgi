<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfq_items', function (Blueprint $table) {
            $table->text('link_1')->nullable()->after('price_1');
            $table->text('link_2')->nullable()->after('price_2');
            $table->text('link_3')->nullable()->after('price_3');
        });
    }
    
    public function down(): void
    {
        Schema::table('rfq_items', function (Blueprint $table) {
            $table->dropColumn(['link_1', 'link_2', 'link_3']);
        });
    }
};
