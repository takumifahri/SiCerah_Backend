<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Administrator — Konfigurasi Parameter SHU (di-lock Ketua setelah RAT)
        Schema::create('shu_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->unique()->constrained('fiscal_years');
            $table->decimal('pct_jasa_modal', 5, 2); // %JM
            $table->decimal('pct_jasa_usaha', 5, 2); // %JU
            $table->decimal('pct_dana_cadangan', 5, 2)->default(0);
            $table->decimal('pct_porsi_anggota', 5, 2)->default(0);
            $table->decimal('pct_jasa_pengurus', 5, 2)->default(0);
            $table->decimal('pct_dana_lain', 5, 2)->default(0);
            $table->boolean('is_locked')->default(false);
            $table->foreignId('locked_by')->nullable()->constrained('users');
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
        });

        // Administrator — Konfigurasi KopPoin (rate poin per aktivitas)
        Schema::create('point_rules', function (Blueprint $table) {
            $table->id();
            $table->enum('activity', ['belanja', 'simpanan', 'setor_panen', 'bayar_cicilan', 'hadir_rapat'])->unique();
            $table->integer('points');
            $table->decimal('per_amount', 15, 2)->nullable(); // contoh: 1 poin per Rp 10.000; null = poin flat per aktivitas
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Administrator — Katalog penukaran poin dan masa berlakunya
        Schema::create('point_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('cost_points');
            $table->integer('quota')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_catalog_items');
        Schema::dropIfExists('point_rules');
        Schema::dropIfExists('shu_parameters');
    }
};
