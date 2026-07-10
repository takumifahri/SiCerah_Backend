<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Engine verifikasi lawan transaksi (jantung produk).
        // Transaksi warga dicatat → sistem memicu konfirmasi ke lawan transaksi via kanal yang sesuai.
        Schema::create('transaction_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions');
            $table->foreignId('counterparty_user_id')->nullable()->constrained('users'); // jika lawan transaksi anggota terdaftar
            $table->string('counterparty_name')->nullable(); // warga non-anggota
            $table->string('counterparty_wa', 20)->nullable(); // nomor WA yang sudah dikunci ke identitas
            $table->enum('channel', ['in_app', 'wa', 'tanda_tangan']);
            $table->string('token', 64)->nullable()->unique(); // token konfirmasi untuk link WA
            $table->string('signature_path')->nullable(); // tanda tangan layar
            $table->enum('status', ['menunggu', 'terverifikasi', 'disengketakan'])->default('menunggu');
            $table->text('dispute_reason')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        // Perilaku 4 — vote persetujuan pengeluaran besar ("RAT mini") & polling keputusan koperasi.
        // Untuk persetujuan pengeluaran, poll di-link ke transaksi via pollable.
        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['persetujuan_pengeluaran', 'keputusan', 'jajak_pendapat'])->default('jajak_pendapat');
            $table->nullableMorphs('pollable'); // transaksi (pengeluaran besar), aspirasi, dll
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->decimal('quorum_pct', 5, 2)->nullable(); // override kuorum default dari cooperative_profiles
            $table->enum('status', ['draft', 'berjalan', 'disetujui', 'ditolak', 'selesai'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('poll_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('polls')->cascadeOnDelete();
            $table->string('label'); // persetujuan pengeluaran cukup "Setuju" / "Tolak"
            $table->timestamps();
        });

        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('polls')->cascadeOnDelete();
            $table->foreignId('poll_option_id')->constrained('poll_options')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();

            $table->unique(['poll_id', 'user_id']); // satu anggota satu suara
        });

        // Kanal aspirasi anggota — usul yang bisa didukung anggota lain lalu diangkat jadi poll
        Schema::create('aspirations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users');
            $table->string('title');
            $table->text('body');
            $table->string('category')->nullable();
            $table->enum('status', ['baru', 'ditinjau', 'ditindaklanjuti', 'ditolak'])->default('baru');
            $table->text('response')->nullable(); // tanggapan pengurus
            $table->foreignId('responded_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('aspiration_supports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aspiration_id')->constrained('aspirations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();

            $table->unique(['aspiration_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aspiration_supports');
        Schema::dropIfExists('aspirations');
        Schema::dropIfExists('poll_votes');
        Schema::dropIfExists('poll_options');
        Schema::dropIfExists('polls');
        Schema::dropIfExists('transaction_verifications');
    }
};
