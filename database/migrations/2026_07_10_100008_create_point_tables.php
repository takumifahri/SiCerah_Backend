<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Anggota — Riwayat Perolehan Poin (saldo = SUM(points); poin minus untuk penukaran)
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users');
            $table->enum('activity', ['belanja', 'simpanan', 'setor_panen', 'bayar_cicilan', 'hadir_rapat', 'penukaran', 'penyesuaian']);
            $table->integer('points'); // signed: earn positif, redeem negatif
            $table->nullableMorphs('pointable'); // sumber poin: sale, saving, meeting, dll
            $table->string('description')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();
        });

        // Anggota request tukar → Bendahara approve → Kasir eksekusi
        Schema::create('point_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users');
            $table->foreignId('point_catalog_item_id')->constrained('point_catalog_items');
            $table->integer('points_cost');
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak', 'diklaim'])->default('menunggu');
            $table->foreignId('approved_by')->nullable()->constrained('users'); // Bendahara
            $table->foreignId('executed_by')->nullable()->constrained('users'); // Kasir
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_redemptions');
        Schema::dropIfExists('point_transactions');
    }
};
