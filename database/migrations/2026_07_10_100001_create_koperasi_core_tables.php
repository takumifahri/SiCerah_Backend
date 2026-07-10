<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Profil koperasi (Administrator — Pengaturan Profil Koperasi, Threshold Announcement, Saldo Kas Awal C0)
        Schema::create('cooperative_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('alamat');
            $table->string('nomor_badan_hukum')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('wa_bot_number', 20)->nullable();
            $table->string('wa_bot_token')->nullable();
            $table->decimal('announcement_threshold', 15, 2)->default(500000); // auto-announce di atas nominal ini
            $table->decimal('member_approval_threshold', 15, 2)->default(1000000); // pengeluaran di atas ini butuh vote anggota ("RAT mini")
            $table->decimal('approval_quorum_pct', 5, 2)->default(50); // % suara setuju minimum untuk vote pengeluaran
            $table->decimal('initial_cash_balance', 15, 2)->default(0); // C0
            $table->timestamps();
        });

        // Periode tutup buku (Administrator — Konfigurasi Periode Tutup Buku)
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // contoh: "Tahun Buku 2026"
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_years');
        Schema::dropIfExists('cooperative_profiles');
    }
};
