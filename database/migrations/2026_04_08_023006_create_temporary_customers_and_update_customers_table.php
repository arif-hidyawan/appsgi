<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom yang kurang di tabel customers
        Schema::table('customers', function (Blueprint $table) {
            $table->string('business_type')->nullable()->after('name');
            $table->string('district')->nullable()->after('billing_address');
            $table->string('city')->nullable()->after('district');
            $table->string('province')->nullable()->after('city');
            $table->string('business_scope')->nullable()->after('phone');
            $table->json('supporting_documents')->nullable()->after('is_active');
        });

        // 2. Buat tabel temporary_customers
        Schema::create('temporary_customers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('email')->nullable();
            
            // Info Perusahaan
            $table->string('business_type')->nullable(); // Perdagangan, Industri, dll
            $table->text('address')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('company_phone')->nullable();
            
            // Info Kontak (Akan masuk ke customer_contacts)
            $table->string('pic_name');
            $table->string('pic_position')->nullable();
            $table->string('pic_phone')->nullable();
            
            // Info Tambahan
            $table->string('business_scope')->nullable(); // Sub Con, End User
            $table->string('payment_terms')->nullable(); // DP/CBD, COD, dll
            $table->json('supporting_documents')->nullable(); // Simpan path array
            
            // Status Approval
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_customers');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'business_type',
                'district',
                'city',
                'province',
                'business_scope',
                'supporting_documents'
            ]);
        });
    }
};