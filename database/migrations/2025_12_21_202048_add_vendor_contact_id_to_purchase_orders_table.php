<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('purchase_orders', function (Blueprint $table) {
        $table->foreignId('vendor_contact_id')
            ->nullable()
            ->after('vendor_id')
            ->constrained('vendor_contacts')
            ->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('purchase_orders', function (Blueprint $table) {
        $table->dropForeign(['vendor_contact_id']);
        $table->dropColumn('vendor_contact_id');
    });
}
};
