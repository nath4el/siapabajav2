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
    Schema::table('pengadaans', function (Blueprint $table) {
        $table->string('metode_pengadaan')->nullable();
    });
}

public function down(): void
{
    Schema::table('pengadaans', function (Blueprint $table) {
        $table->dropColumn('metode_pengadaan');
    });
}
};
