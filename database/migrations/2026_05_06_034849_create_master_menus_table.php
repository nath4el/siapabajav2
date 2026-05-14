<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('master_menus', function (Blueprint $table) {

            $table->id();

            // kategori dropdown
            // contoh:
            // metode_pengadaan
            // status_pekerjaan
            // jenis_pengadaan
            // tahun_pengadaan
            $table->string('category');

            // isi dropdown
            $table->string('nama');

            // status aktif / nonaktif
            $table->boolean('is_active')->default(true);

            // urutan menu
            $table->integer('order_index')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_menus');
    }
};