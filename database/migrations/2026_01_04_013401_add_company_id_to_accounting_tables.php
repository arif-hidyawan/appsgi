<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. MATIKAN CONSTRAINT & HAPUS SEMUA TABEL LAMA (SESUAI REQUEST)
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('accounts');
        Schema::enableForeignKeyConstraints();

        // 2. BUAT ULANG TABEL ACCOUNTS (LANGSUNG PAKAI COMPANY_ID)
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            // Kolom Wajib Multi-Company
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            
            $table->string('code'); // Tidak unique global
            $table->string('name');
            $table->enum('type', ['H', 'D'])->default('D');
            $table->enum('nature', [
                'asset', 'liability', 'equity', 'revenue', 'expense', 
                'cogs', 'other_revenue', 'other_expense'
            ])->default('asset');
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_cash_bank')->default(false);
            $table->boolean('is_inventory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique per Perusahaan (Kode boleh sama asal beda PT)
            $table->unique(['company_id', 'code']);
        });

        // 3. BUAT ULANG TABEL JOURNALS (LANGSUNG PAKAI COMPANY_ID)
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            // Kolom Wajib Multi-Company
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            
            $table->date('journal_date')->index();
            $table->text('memo')->nullable();
            $table->string('source', 64)->nullable()->index();
            $table->string('reference', 128)->nullable()->index();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        // 4. BUAT ULANG TABEL JOURNAL LINES
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->enum('direction', ['debit', 'credit']);
            $table->decimal('amount', 16, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        // Drop logic standar
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('accounts');
        Schema::enableForeignKeyConstraints();
    }
};