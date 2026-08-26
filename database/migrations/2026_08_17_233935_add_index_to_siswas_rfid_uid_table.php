<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom 'nis' baru ditambahkan di migration 2026_08_19_000001,
        // jadi migration ini (tanggal 17) harus skip kalau kolomnya belum ada
        // (misal saat migrate:fresh dari database kosong).
        if (Schema::hasColumn('siswas', 'nis')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->index('nis');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('siswas', 'nis')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->dropIndex(['nis']);
            });
        }
    }
};