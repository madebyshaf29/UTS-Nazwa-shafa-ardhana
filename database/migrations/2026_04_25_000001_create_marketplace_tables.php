<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('nama_produk');
            $table->enum('kategori', ['pakan', 'bibit', 'alat']);
            $table->foreignId('komoditas_id')->nullable()->constrained('master_komoditas')->nullOnDelete();
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('harga');
            $table->unsignedInteger('stok')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('marketplace_carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique('user_id');
            $table->foreign('user_id')->references('id_user')->on('users')->cascadeOnDelete();
        });

        Schema::create('marketplace_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('marketplace_carts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('marketplace_products')->cascadeOnDelete();
            $table->unsignedInteger('qty');
            $table->unsignedInteger('harga_saat_dimasukkan');
            $table->timestamps();

            $table->unique(['cart_id', 'product_id']);
        });

        Schema::create('marketplace_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('ongkir')->default(0);
            $table->unsignedInteger('total');
            $table->enum('status_pembayaran', ['menunggu_pembayaran', 'dibayar', 'gagal', 'expired'])->default('menunggu_pembayaran');
            $table->enum('status_pesanan', ['menunggu_pembayaran', 'diproses', 'dikirim', 'selesai', 'dibatalkan'])->default('menunggu_pembayaran');
            $table->string('payment_reference')->nullable();
            $table->json('shipping_payload')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id_user')->on('users')->cascadeOnDelete();
        });

        Schema::create('marketplace_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('marketplace_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('marketplace_products')->restrictOnDelete();
            $table->string('nama_produk');
            $table->unsignedInteger('qty');
            $table->unsignedInteger('harga');
            $table->unsignedInteger('subtotal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_order_items');
        Schema::dropIfExists('marketplace_orders');
        Schema::dropIfExists('marketplace_cart_items');
        Schema::dropIfExists('marketplace_carts');
        Schema::dropIfExists('marketplace_products');
    }
};
