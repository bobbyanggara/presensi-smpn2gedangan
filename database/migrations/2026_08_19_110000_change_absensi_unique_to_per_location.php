<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropUnique('absensis_siswa_tanggal_unique');
            $table->unique(['siswa_id', 'tanggal', 'location_id'], 'absensis_siswa_tanggal_lokasi_unique');
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropUnique('absensis_siswa_tanggal_lokasi_unique');
            $table->unique(['siswa_id', 'tanggal'], 'absensis_siswa_tanggal_unique');
        });
    }
};
