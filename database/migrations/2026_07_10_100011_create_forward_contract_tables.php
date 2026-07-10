<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Logistik — Forward Contract: Kopdes sebagai pembeli (kunci harga panen) atau penjual (kontrak suplai ke buyer kota)
        Schema::create('forward_contracts', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['beli', 'jual']);
            $table->string('counterparty_name'); // petani/UMKM lokal atau buyer/pabrik kota
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->string('commodity');
            $table->string('unit')->default('kg');
            $table->decimal('price_per_unit', 15, 2);
            $table->decimal('target_quantity', 12, 2);
            $table->decimal('fulfilled_quantity', 12, 2)->default(0);
            $table->date('deadline');
            $table->enum('status', ['aktif', 'selesai', 'batal'])->default('aktif');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // Pecah target kontrak jual menjadi kuota per kelompok tani
        Schema::create('forward_contract_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forward_contract_id')->constrained('forward_contracts')->cascadeOnDelete();
            $table->string('group_name'); // nama kelompok tani
            $table->decimal('quota_quantity', 12, 2);
            $table->decimal('fulfilled_quantity', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forward_contract_quotas');
        Schema::dropIfExists('forward_contracts');
    }
};
