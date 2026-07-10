<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bendahara — Pencatatan simpanan pokok/wajib/sukarela (basis Jasa Modal)
        Schema::create('savings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users');
            $table->enum('type', ['pokok', 'wajib', 'sukarela']);
            $table->enum('direction', ['setor', 'tarik'])->default('setor');
            $table->decimal('amount', 15, 2);
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Bendahara/Ketua — Manajemen Simpan Pinjam (nilai besar dieskalasi ke Ketua)
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users');
            $table->decimal('amount', 15, 2);
            $table->text('purpose')->nullable();
            $table->unsignedTinyInteger('tenor_months');
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->enum('approval_level', ['bendahara', 'ketua'])->default('bendahara');
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions'); // pencairan
            $table->timestamps();
        });

        // Jadwal cicilan + reminder otomatis via WhatsApp
        Schema::create('loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->unsignedTinyInteger('installment_number');
            $table->date('due_date');
            $table->decimal('amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->enum('status', ['belum_bayar', 'sebagian', 'lunas', 'terlambat'])->default('belum_bayar');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['loan_id', 'installment_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_installments');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('savings');
    }
};
