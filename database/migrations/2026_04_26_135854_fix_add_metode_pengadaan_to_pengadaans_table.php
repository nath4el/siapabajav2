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
        if (! Schema::hasColumn('pengadaans', 'metode_pengadaan')) {
            Schema::table('pengadaans', function (Blueprint $table) {
                $table->string('metode_pengadaan')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('pengadaans', 'metode_pengadaan')) {
            Schema::table('pengadaans', function (Blueprint $table) {
                $table->dropColumn('metode_pengadaan');
            });
        }
    }
};