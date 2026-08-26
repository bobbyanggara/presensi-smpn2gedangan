<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->unique(['siswa_id', 'tanggal'], 'absensis_siswa_tanggal_unique');
            $table->index('tanggal', 'absensis_tanggal_index');
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropUnique('absensis_siswa_tanggal_unique');
            $table->dropIndex('absensis_tanggal_index');
        });
    }
};
