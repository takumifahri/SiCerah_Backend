<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Approval Center generik: pinjaman, pengeluaran besar, void transaksi, tukar poin, dll
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable'); // loan, transaction, sale (void), point_redemption, ...
            $table->string('action'); // approve_loan, approve_void, approve_expense, ...
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->foreignId('requested_by')->constrained('users');
            $table->string('approver_role'); // bendahara / ketua
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamp('due_at')->nullable(); // Ketua — Eskalasi Otomatis jika lewat batas waktu
            $table->timestamp('escalated_at')->nullable();
            $table->timestamps();
        });

        // Ketua — Delegasi Approval sementara (nice to have)
        Schema::create('approval_delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_user_id')->constrained('users');
            $table->foreignId('to_user_id')->constrained('users');
            $table->string('scope')->nullable(); // batasi jenis approval yang didelegasikan
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();
        });

        // Pengawas — Audit Log Aktivitas (siapa, apa, kapan, dari device mana)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('action'); // created, updated, verified, approved, login, ...
            $table->nullableMorphs('auditable');
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        // Pengawas — Flag transaksi mencurigakan (read-add only)
        Schema::create('audit_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions');
            $table->foreignId('flagged_by')->constrained('users');
            $table->text('note');
            $table->enum('status', ['open', 'ditindaklanjuti', 'selesai'])->default('open');
            $table->timestamps();
        });

        Schema::create('audit_flag_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_flag_id')->constrained('audit_flags')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->text('comment');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_flag_comments');
        Schema::dropIfExists('audit_flags');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('approval_delegations');
        Schema::dropIfExists('approvals');
    }
};
