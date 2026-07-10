<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pos anggaran per tahun buku (Anggota — Realisasi Anggaran Real-Time)
        Schema::create('budget_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years');
            $table->string('name'); // operasional, komoditas, sosial, dll
            $table->decimal('planned_amount', 15, 2);
            $table->timestamps();
        });

        // Ledger append-only: kas masuk/keluar (Bendahara). Koreksi = entri baru yang mereferensi entri salah.
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years');
            $table->foreignId('budget_post_id')->nullable()->constrained('budget_posts');
            $table->enum('type', ['masuk', 'keluar']);
            $table->decimal('amount', 15, 2);
            $table->string('category'); // simpanan, angsuran, omzet_gerai, operasional, pencairan_pinjaman, supplier, dll
            $table->string('vendor')->nullable(); // vendor / penerima
            $table->text('description')->nullable();
            $table->string('proof_path')->nullable(); // bukti: foto kuitansi/nota atau PDF struk digital; wajib untuk kas keluar, tanpa bukti → tetap "Menunggu Verifikasi"
            // Hanya "terverifikasi" yang masuk perhitungan kas & SHU
            $table->enum('status', ['menunggu_verifikasi', 'terverifikasi', 'disengketakan'])->default('menunggu_verifikasi');
            $table->enum('source', ['manual', 'pos', 'voice', 'whatsapp'])->default('manual');
            $table->foreignId('input_by')->constrained('users');
            $table->foreignId('verified_by')->nullable()->constrained('users'); // dual approval petugas
            $table->foreignId('approved_by')->nullable()->constrained('users'); // approval Ketua di atas threshold
            $table->foreignId('corrects_transaction_id')->nullable()->constrained('transactions'); // koreksi append-only
            $table->string('integrity_hash', 64)->nullable(); // Pengawas — verifikasi integritas ledger
            $table->string('prev_hash', 64)->nullable();
            $table->uuid('client_uuid')->nullable()->unique(); // idempotency untuk offline-first sync
            $table->timestamp('transacted_at');
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('transacted_at');
        });

        // Kasir — Tutup Kas Harian + Bendahara — Rekonsiliasi Kas Harian
        Schema::create('cash_register_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kasir_id')->constrained('users');
            $table->date('closing_date');
            $table->decimal('system_total', 15, 2);
            $table->decimal('physical_total', 15, 2);
            $table->decimal('difference', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['diajukan', 'dikonfirmasi', 'selisih'])->default('diajukan');
            $table->foreignId('reconciled_by')->nullable()->constrained('users');
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();

            $table->unique(['kasir_id', 'closing_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_register_closings');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('budget_posts');
    }
};
