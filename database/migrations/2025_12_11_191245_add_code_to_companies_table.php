<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Menambahkan kolom code setelah id
            $table->string('code')->nullable()->unique()->after('id');
        });
    }
    
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
