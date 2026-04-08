<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('journals', function (Blueprint $table) {
        $table->string('expense_category', 10)->nullable()->after('is_real')->comment('Kategori Biaya: F1, F2, F3, F5, TF1');
    });
}

public function down(): void
{
    Schema::table('journals', function (Blueprint $table) {
        $table->dropColumn('expense_category');
    });
}
};
