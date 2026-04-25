<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->string('lokasi')->nullable()->after('kategori')->default('Sidoarjo');
            $table->string('estimasi_pengiriman')->nullable()->after('lokasi')->default('1-3 Hari');
            $table->text('spesifikasi')->nullable()->after('deskripsi');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->dropColumn(['lokasi', 'estimasi_pengiriman', 'spesifikasi']);
        });
    }
};
