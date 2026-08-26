<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom 'no_hp_ortu' referensi lama yang sudah tidak ada di skema baru,
        // dan kolom 'foto' sudah ditambahkan ulang secara aman di migration
        // 2026_08_19_000002_add_foto_to_siswas_table.php. Migration ini di-skip
        // pada instalasi baru (database kosong) supaya tidak error.
        if (!Schema::hasColumn('siswas', 'foto') && Schema::hasColumn('siswas', 'no_hp_ortu')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->string('foto')->nullable()->after('no_hp_ortu');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('siswas', 'foto')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->dropColumn('foto');
            });
        }
    }
};