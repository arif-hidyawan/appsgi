<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('quotations', function (Blueprint $table) {
        $table->foreignId('customer_contact_id')
            ->nullable()
            ->after('customer_id')
            ->constrained('customer_contacts')
            ->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('quotations', function (Blueprint $table) {
        $table->dropForeign(['customer_contact_id']);
        $table->dropColumn('customer_contact_id');
    });
}
};
