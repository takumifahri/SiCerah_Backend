<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('no_anggota', 20)->nullable()->unique()->after('role');
            $table->enum('status_keanggotaan', ['aktif', 'pasif', 'keluar'])->default('aktif')->after('no_anggota');
            $table->boolean('is_active')->default(true)->after('status_keanggotaan'); // aktif/nonaktif akun pengurus (RBAC)
            $table->date('tanggal_lahir')->nullable()->after('is_active'); // segmentasi partisipasi per kelompok usia
            $table->timestamp('wa_verified_at')->nullable()->after('tanggal_lahir'); // nomor WA dikunci ke identitas (anti-substitusi verifikasi)
        });

        // Sekretaris — Manajemen Status Keanggotaan (riwayat perubahan + alasan)
        Schema::create('member_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('old_status');
            $table->string('new_status');
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_status_histories');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['no_anggota', 'status_keanggotaan', 'is_active', 'tanggal_lahir', 'wa_verified_at']);
        });
    }
};
