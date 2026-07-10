<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Snapshot laba berjalan L_t & saldo kas — sumber grafik SHU fluktuatif ala Stockbit
        Schema::create('financial_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years');
            $table->date('snapshot_date');
            $table->decimal('running_profit', 15, 2); // L_t, bisa negatif (koperasi rugi → tampilan jujur)
            $table->decimal('cash_balance', 15, 2);
            $table->decimal('total_assets', 15, 2)->nullable();
            $table->timestamps();

            $table->unique(['fiscal_year_id', 'snapshot_date']);
        });

        // SHU final per anggota per tahun buku + status pencairan (Histori SHU Tahun Lalu)
        Schema::create('shu_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years');
            $table->foreignId('member_id')->constrained('users');
            $table->decimal('jasa_modal_amount', 15, 2)->default(0);
            $table->decimal('jasa_usaha_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('status', ['belum_cair', 'cair'])->default('belum_cair');
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamps();

            $table->unique(['fiscal_year_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shu_distributions');
        Schema::dropIfExists('financial_snapshots');
    }
};
