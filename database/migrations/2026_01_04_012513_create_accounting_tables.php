<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Tabel Accounts
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('type', ['H', 'D'])->default('D')->comment('H=Header, D=Detail');
            $table->enum('nature', [
                'asset', 'liability', 'equity', 'revenue', 'expense', 
                'cogs', 'other_revenue', 'other_expense'
            ])->default('asset');
            
            // Relasi ke diri sendiri (Induk)
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            
            $table->boolean('is_cash_bank')->default(false);
            $table->boolean('is_inventory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Tabel Journals
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->date('journal_date')->index();
            $table->text('memo')->nullable();
            $table->string('source', 64)->nullable()->index();
            $table->string('reference', 128)->nullable()->index();
            
            // Asumsi ada tabel users, nullable agar aman
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            
            $table->timestamps();
        });

        // 3. Tabel Journal Lines
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
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('accounts');
    }
};