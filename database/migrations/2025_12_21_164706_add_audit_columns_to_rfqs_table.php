<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('rfqs', function (Blueprint $table) {
        // Tambahkan kolom created_by dan updated_by
        $table->unsignedBigInteger('created_by')->nullable()->after('attachment');
        $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');

        // Opsional: Tambahkan foreign key agar data konsisten dengan tabel users
        $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
    });
}

public function down(): void
{
    Schema::table('rfqs', function (Blueprint $table) {
        $table->dropForeign(['created_by']);
        $table->dropForeign(['updated_by']);
        $table->dropColumn(['created_by', 'updated_by']);
    });
}
};
