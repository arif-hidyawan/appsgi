<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receives', function (Blueprint $table) {
            // PIC Vendor
            $table->foreignId('vendor_contact_id')->nullable()->after('vendor_id')->constrained('vendor_contacts')->nullOnDelete();
            
            // Audit Trail
            $table->foreignId('created_by')->nullable()->after('vendor_delivery_number')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });
    }
    
    public function down(): void
    {
        Schema::table('goods_receives', function (Blueprint $table) {
            $table->dropForeign(['vendor_contact_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['vendor_contact_id', 'created_by', 'updated_by']);
        });
    }
};
