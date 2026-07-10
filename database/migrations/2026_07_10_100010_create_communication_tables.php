<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sekretaris — CMS Announcement (in-app, WA blast via Fonnte, terjadwal, auto-announce threshold)
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->enum('category', ['keuangan', 'rapat', 'komoditas', 'umum'])->default('umum');
            $table->enum('channel', ['in_app', 'wa', 'keduanya'])->default('in_app');
            $table->string('attachment_path')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->boolean('is_auto')->default(false); // auto-announce pengeluaran di atas threshold
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // Sekretaris — Tracking Read Receipt + reminder 24 jam
        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();

            $table->unique(['announcement_id', 'user_id']);
        });

        // Sekretaris — Manajemen Rapat (jadwal, agenda, notulensi)
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['rat', 'bulanan', 'luar_biasa'])->default('bulanan');
            $table->text('agenda')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('scheduled_at');
            $table->string('minutes_path')->nullable(); // notulensi pasca-rapat
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // Anggota — RSVP + absensi digital (attended_at terisi = hadir → poin rapat)
        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('rsvp', ['belum', 'hadir', 'tidak_hadir'])->default('belum');
            $table->timestamp('attended_at')->nullable();
            $table->timestamps();

            $table->unique(['meeting_id', 'user_id']);
        });

        // Sekretaris — Manajemen Dokumen Koperasi (AD/ART, SK, notulensi RAT)
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('category', ['ad_art', 'sk_pendirian', 'notulensi_rat', 'sk_pengurus', 'lainnya'])->default('lainnya');
            $table->string('file_path');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('meeting_participants');
        Schema::dropIfExists('meetings');
        Schema::dropIfExists('announcement_reads');
        Schema::dropIfExists('announcements');
    }
};
