<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Index unique lama (siswa_id, tanggal) dipakai MySQL sebagai index
        // pendukung foreign key 'siswa_id'. Kita tambahkan index terpisah untuk
        // siswa_id dulu, supaya saat unique itu di-drop, foreign key masih
        // punya index pendukung dan tidak error "Cannot drop index ... needed
        // in a foreign key constraint".
        if (!$this->indexExists('absensis', 'absensis_siswa_id_index')) {
            Schema::table('absensis', function (Blueprint $table) {
                $table->index('siswa_id', 'absensis_siswa_id_index');
            });
        }

        if ($this->indexExists('absensis', 'absensis_siswa_tanggal_unique')) {
            Schema::table('absensis', function (Blueprint $table) {
                $table->dropUnique('absensis_siswa_tanggal_unique');
            });
        }

        if (!$this->indexExists('absensis', 'absensis_siswa_tanggal_lokasi_unique')) {
            Schema::table('absensis', function (Blueprint $table) {
                $table->unique(['siswa_id', 'tanggal', 'location_id'], 'absensis_siswa_tanggal_lokasi_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('absensis', 'absensis_siswa_tanggal_lokasi_unique')) {
            Schema::table('absensis', function (Blueprint $table) {
                $table->dropUnique('absensis_siswa_tanggal_lokasi_unique');
            });
        }

        if (!$this->indexExists('absensis', 'absensis_siswa_tanggal_unique')) {
            Schema::table('absensis', function (Blueprint $table) {
                $table->unique(['siswa_id', 'tanggal'], 'absensis_siswa_tanggal_unique');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $result = $connection->select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );

        return count($result) > 0;
    }
};
