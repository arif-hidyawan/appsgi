<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('sales_returns', function (Blueprint $table) {
        $table->foreignId('created_by')->nullable()->constrained('users')->after('status');
        $table->foreignId('updated_by')->nullable()->constrained('users')->after('created_by');
    });
}

public function down(): void
{
    Schema::table('sales_returns', function (Blueprint $table) {
        $table->dropForeign(['created_by']);
        $table->dropForeign(['updated_by']);
        $table->dropColumn(['created_by', 'updated_by']);
    });
}
};
