<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->nullable()->unique();
            $table->string('name');
            $table->string('unit')->default('pcs');
            $table->decimal('price', 15, 2); // harga non-anggota / umum
            $table->decimal('member_price', 15, 2)->nullable(); // harga anggota — basis struk pembanding & "kamu hemat Rp X"
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0); // Logistik — Alert Stok Menipis
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Kasir — POS penjualan; member_id terisi jika anggota scan QR (kontribusi U_i)
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('kasir_id')->constrained('users');
            $table->foreignId('member_id')->nullable()->constrained('users');
            $table->string('customer_wa', 20)->nullable(); // non-anggota: tujuan struk WA pembanding harga ("umpan" akuisisi)
            $table->decimal('total', 15, 2);
            $table->decimal('member_savings', 15, 2)->default(0); // anggota: hemat vs harga umum; non-anggota: selisih yang "hilang"
            $table->string('receipt_pdf_path')->nullable(); // PDF struk digital — dipakai juga sebagai bukti faktur di transactions.proof_path
            $table->timestamp('receipt_sent_at')->nullable(); // struk digital terkirim via WA
            $table->enum('status', ['selesai', 'menunggu_void', 'void'])->default('selesai'); // void butuh approval Bendahara
            $table->text('void_reason')->nullable();
            $table->foreignId('void_approved_by')->nullable()->constrained('users');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions');
            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->integer('qty');
            $table->decimal('unit_price', 15, 2); // harga yang dikenakan (anggota atau umum)
            $table->decimal('regular_unit_price', 15, 2)->nullable(); // harga umum saat transaksi — untuk struk pembanding
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('products');
    }
};
