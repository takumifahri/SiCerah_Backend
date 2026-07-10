<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Logistik — Database Mitra Supplier & UMKM (termasuk petani/pengrajin desa)
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('commodity')->nullable();
            $table->boolean('is_local_umkm')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Logistik — Barang Masuk (satu surat jalan bisa berisi banyak produk)
        Schema::create('stock_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->string('surat_jalan_path')->nullable(); // foto surat jalan
            $table->foreignId('received_by')->constrained('users');
            $table->timestamp('received_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_receipt_id')->constrained('stock_receipts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->integer('qty');
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->timestamps();
        });

        // Logistik — Stok Opname (fisik vs sistem)
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->date('opname_date');
            $table->foreignId('conducted_by')->constrained('users');
            $table->enum('status', ['draft', 'selesai'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->integer('system_qty');
            $table->integer('physical_qty');
            $table->integer('difference');
            $table->timestamps();
        });

        // Logistik — Evaluasi Supplier (dasar keputusan reorder)
        Schema::create('supplier_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('evaluated_by')->constrained('users');
            $table->string('period'); // contoh: "2026-07"
            $table->unsignedTinyInteger('delivery_score'); // ketepatan waktu, 1-5
            $table->unsignedTinyInteger('quality_score');
            $table->unsignedTinyInteger('price_score'); // konsistensi harga
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_evaluations');
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('stock_receipt_items');
        Schema::dropIfExists('stock_receipts');
        Schema::dropIfExists('suppliers');
    }
};
